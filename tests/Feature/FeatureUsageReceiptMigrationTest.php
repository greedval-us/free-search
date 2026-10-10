<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeatureUsageReceiptMigrationTest extends TestCase
{
    use RefreshDatabase;

    public static function reports(): array
    {
        return [
            'bluesky' => ['bluesky', 'accounts', 'account_input'],
            'mastodon' => ['mastodon', 'accounts', 'account_input'],
            'youtube' => ['youtube', 'channels', 'channel_input'],
            'telegram' => ['telegram', 'groups', 'chat_username'],
            'site intel' => ['site_intel', 'targets', 'target_url'],
        ];
    }

    #[DataProvider('reports')]
    public function test_upgrade_preserves_a_legacy_reports_original_charge_and_single_refund(string $module, string $inputs, string $target): void
    {
        config()->set('app.timezone', 'Europe/Moscow');
        $this->travelTo(CarbonImmutable::parse('2026-10-10 23:59:59', 'Europe/Moscow'));
        $migration = require base_path('database/migrations/2026_10_10_022106_create_feature_usage_receipts_table.php');
        $migration->down();
        $user = User::factory()->create();
        $resource = str_replace('_', '-', $module).'.analytics';
        FeatureUsageDaily::query()->create([
            'user_id' => $user->id, 'feature' => $resource, 'usage_date' => now()->startOfDay(), 'used' => 2,
        ]);
        [$table, $reportId] = $this->legacyReport($user, $module, $inputs, $target);

        $migration->up();

        $receiptId = DB::table($table)->where('id', $reportId)->value('quota_receipt_id');
        $this->assertNotNull($receiptId);
        $this->assertSame(2, FeatureUsageDaily::query()->where('user_id', $user->id)->value('used'));
        $this->travelTo(CarbonImmutable::parse('2026-10-11 00:00:01', 'Europe/Moscow'));
        $service = app(FeatureAccessServiceInterface::class);
        $service->consumeResource($user, $resource);
        $service->refund($user, $receiptId);
        $service->refund($user, $receiptId);

        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $user->id)->whereDate('usage_date', '2026-10-10')->value('used'));
        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $user->id)->whereDate('usage_date', '2026-10-11')->value('used'));
    }

    public function test_upgrade_does_not_invent_debits_for_staff_or_missing_counters(): void
    {
        $migration = require base_path('database/migrations/2026_10_10_022106_create_feature_usage_receipts_table.php');
        $migration->down();
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_ADMIN]);
        $user = User::factory()->create();
        FeatureUsageDaily::query()->create([
            'user_id' => $staff->id, 'feature' => 'bluesky.analytics', 'usage_date' => now()->startOfDay(), 'used' => 1,
        ]);
        $this->legacyReport($staff, 'bluesky', 'accounts', 'account_input');
        $this->legacyReport($user, 'bluesky', 'accounts', 'account_input');

        $migration->up();

        $this->assertDatabaseCount('feature_usage_receipts', 0);
        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $staff->id)->value('used'));
    }

    /** @return array{string, int} */
    private function legacyReport(User $user, string $module, string $inputs, string $target): array
    {
        $siteIntel = $module === 'site_intel';
        $scheduleTable = $siteIntel ? 'site_intel_report_schedules' : $module.'_analytics_schedules';
        $reportTable = $siteIntel ? 'site_intel_scheduled_reports' : $module.'_analytics_reports';
        $schedule = [
            'user_id' => $user->id, 'name' => 'Legacy test schedule', $inputs => '["example"]',
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow', 'next_run_at' => now(),
        ];
        if ($siteIntel) {
            $schedule['report_type'] = 'analytics';
        }
        $scheduleId = DB::table($scheduleTable)->insertGetId($schedule);
        $report = [
            'user_id' => $user->id, 'schedule_id' => $scheduleId, $target => 'example',
            'scheduled_for' => now(), 'date_from' => now()->subDay(), 'date_to' => now(),
            'status' => 'pending', 'quota_charged' => true, 'quota_charged_at' => now(),
        ];
        if ($siteIntel) {
            $report['report_type'] = 'analytics';
        }

        return [$reportTable, DB::table($reportTable)->insertGetId($report)];
    }
}
