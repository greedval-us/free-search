<?php

namespace Tests\Feature;

use App\Models\BlueskyAnalyticsReport;
use App\Models\BlueskyAnalyticsSchedule;
use App\Models\MastodonAnalyticsReport;
use App\Models\MastodonAnalyticsSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SocialAnalyticsReportsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        $this->travelTo(now()->setDate(2026, 10, 9)->setTime(8, 0));
        config(['bluesky_analytics_reports.queue.connection' => 'database', 'mastodon_analytics_reports.queue.connection' => 'database', 'telegram_bot.enabled' => false]);
    }

    public static function modules(): array
    {
        return [
            'Bluesky' => ['bluesky', BlueskyAnalyticsSchedule::class, BlueskyAnalyticsReport::class, ['@Alice.bsky.social', 'https://bsky.app/profile/Bob.bsky.social'], ['alice.bsky.social', 'bob.bsky.social']],
            'Mastodon' => ['mastodon', MastodonAnalyticsSchedule::class, MastodonAnalyticsReport::class, ['@Alice@mastodon.social', 'https://mastodon.social/@Bob'], ['alice@mastodon.social', 'bob@mastodon.social']],
        ];
    }

    #[DataProvider('modules')]
    public function test_configuration_normalizes_accounts_without_spending_quota(string $module, string $scheduleClass, string $reportClass, array $accounts, array $normalized): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("/$module/analytics/reports/schedules", $this->input($accounts))
            ->assertCreated()->assertJsonPath('data.accounts', $normalized);
        $schedule = $scheduleClass::query()->sole();
        $this->assertSame($user->id, $schedule->user_id);
        $this->assertSame('09:00', $schedule->send_time);
        $this->getJson("/$module/analytics/reports")->assertOk()
            ->assertJsonPath('data.schedules.0.accounts', $normalized)
            ->assertJsonPath('data.maxAccounts', 3)->assertJsonPath('data.botLinked', false);
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    #[DataProvider('modules')]
    public function test_duplicate_and_malicious_accounts_and_invalid_options_are_rejected(string $module, string $scheduleClass, string $reportClass, array $accounts, array $normalized): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([
            ['accounts' => [$accounts[0], $normalized[0]]],
            ['accounts' => ['https://127.0.0.1/@alice']],
            ['accounts' => [$accounts[0].' '.$accounts[1]]],
            ['accounts' => [...$accounts, ...$accounts]],
            ['interval' => '2'], ['sendTime' => '25:30'], ['timezone' => 'unknown'], ['name' => ' '],
        ] as $invalid) {
            $this->postJson("/$module/analytics/reports/schedules", $this->input($accounts, $invalid))->assertUnprocessable();
        }
        $this->assertSame(0, $scheduleClass::query()->count());
    }

    #[DataProvider('modules')]
    public function test_owner_isolation_applies_to_history_control_and_documents(string $module, string $scheduleClass, string $reportClass, array $accounts, array $normalized): void
    {
        $this->actingAs(User::factory()->create())->postJson("/$module/analytics/reports/schedules", $this->input($accounts))->assertCreated();
        $schedule = $scheduleClass::query()->sole();
        $report = $this->report($schedule, $normalized[0]);
        $this->actingAs(User::factory()->create());
        $this->getJson("/$module/analytics/reports")->assertJsonCount(0, 'data.schedules')->assertJsonCount(0, 'data.reports.data');
        $this->patchJson("/$module/analytics/reports/schedules/$schedule->id", ['action' => 'pause'])->assertNotFound();
        $this->postJson("/$module/analytics/reports/schedules/$schedule->id/run")->assertNotFound();
        $this->deleteJson("/$module/analytics/reports/schedules/$schedule->id")->assertNotFound();
        $this->get("/$module/analytics/reports/$report->id/view")->assertNotFound();
        $this->getJson("/$module/analytics/reports/$report->id/download/json")->assertNotFound();
    }

    #[DataProvider('modules')]
    public function test_saved_documents_survive_cache_expiry_and_deleted_schedule_without_api_calls(string $module, string $scheduleClass, string $reportClass, array $accounts, array $normalized): void
    {
        $this->actingAs(User::factory()->create())->postJson("/$module/analytics/reports/schedules", $this->input($accounts))->assertCreated();
        $report = $this->report($scheduleClass::query()->sole(), $normalized[0]);
        Cache::flush();
        $this->get("/$module/analytics/reports/$report->id/view?locale=ru")
            ->assertOk()->assertSee($normalized[0])->assertSee('Период публикаций')->assertSee('2026-10-02')->assertSee('2026-10-08')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get("/$module/analytics/reports/$report->id/download/html")->assertOk()->assertDownload();
        $this->getJson("/$module/analytics/reports/$report->id/download/json")->assertOk()->assertDownload()->assertJsonPath('range.accountInput', $normalized[0]);
        $this->getJson("/$module/analytics/reports")->assertJsonPath('data.reports.data.0.accountInput', $normalized[0])->assertJsonMissingPath('data.reports.data.0.data');
        $report->schedule->delete();
        $this->getJson("/$module/analytics/reports/$report->id/download/json")->assertOk()->assertJsonPath('summary.postsCount', 2);
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    #[DataProvider('modules')]
    public function test_pause_resume_and_manual_run_preserve_next_automatic_run(string $module, string $scheduleClass, string $reportClass, array $accounts, array $normalized): void
    {
        Bus::fake();
        $this->actingAs(User::factory()->create())->postJson("/$module/analytics/reports/schedules", $this->input($accounts))->assertCreated();
        $schedule = $scheduleClass::query()->sole();
        $this->patchJson("/$module/analytics/reports/schedules/$schedule->id", ['action' => 'pause'])->assertOk();
        $this->assertFalse($schedule->fresh()->enabled);
        $this->patchJson("/$module/analytics/reports/schedules/$schedule->id", ['action' => 'resume'])->assertOk();
        $due = $schedule->fresh()->next_run_at;
        $this->postJson("/$module/analytics/reports/schedules/$schedule->id/run")->assertAccepted();
        $this->assertSame(2, $reportClass::query()->count());
        $this->assertTrue($due->equalTo($schedule->fresh()->next_run_at));
        $this->deleteJson("/$module/analytics/reports/schedules/$schedule->id")->assertOk();
        $this->assertTrue($schedule->fresh()->trashed());
    }

    #[DataProvider('modules')]
    public function test_unfinished_reports_and_guests_are_inaccessible_and_access_rules_cover_tab(string $module, string $scheduleClass, string $reportClass, array $accounts, array $normalized): void
    {
        $this->getJson("/$module/analytics/reports")->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson("/$module/analytics/reports/schedules", $this->input($accounts))->assertCreated();
        $report = $this->report($scheduleClass::query()->sole(), $normalized[0]);
        $report->update(['status' => 'pending', 'data' => null, 'completed_at' => null]);
        $this->get("/$module/analytics/reports/$report->id/view")->assertNotFound();
        $this->getJson("/$module/analytics/reports/$report->id/download/json")->assertNotFound();
        config()->set('access.plans.free', ["$module.analytics" => 0]);
        $this->getJson("/$module/analytics/reports")->assertForbidden();
        $this->postJson("/$module/analytics/reports/schedules", $this->input($accounts))->assertForbidden();
        $this->getJson("/$module?tab=reports")->assertForbidden();
    }

    private function input(array $accounts, array $overrides = []): array
    {
        return [...['name' => 'Weekly accounts', 'accounts' => $accounts, 'interval' => '7', 'sendTime' => '09:00', 'timezone' => 'Europe/Moscow', 'sendToBot' => false], ...$overrides];
    }

    private function report(Model $schedule, string $account): Model
    {
        return $schedule->reports()->create([
            'user_id' => $schedule->user_id, 'account_input' => $account, 'scheduled_for' => now(),
            'date_from' => now()->subWeek()->startOfDay(), 'date_to' => now()->subDay()->endOfDay(),
            'status' => 'completed', 'completed_at' => now(),
            'data' => ['meta' => ['mode' => 'account', 'target' => $account], 'range' => ['accountInput' => $account, 'dateFrom' => '2026-10-02', 'dateTo' => '2026-10-08', 'timezone' => 'Europe/Moscow', 'periodDays' => 7], 'summary' => ['postsCount' => 2], 'methodology' => ['complete' => true, 'metricsBasis' => 'cumulative_at_collection']],
        ]);
    }
}
