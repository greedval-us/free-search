<?php

namespace Tests\Feature;

use App\Models\TelegramAnalyticsReport;
use App\Models\TelegramAnalyticsSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TelegramAnalyticsReportsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 10, 9)->setTime(8, 0));
        Http::preventStrayRequests();
        config(['telegram_analytics_reports.queue.connection' => 'database', 'telegram_bot.enabled' => false]);
    }

    public function test_creates_normalized_schedule_without_charging_for_configuration(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/telegram/analytics/reports/schedules', $this->input())
            ->assertCreated();
        $schedule = TelegramAnalyticsSchedule::query()->firstOrFail();
        $this->assertSame(['publicgroup', 'anothergroup'], $schedule->groups);
        $this->assertSame('7', $schedule->interval);
        $this->assertSame('09:00', $schedule->send_time);
        $this->assertSame($user->id, $schedule->user_id);
        $this->getJson('/telegram/analytics/reports')->assertOk()
            ->assertJsonPath('data.schedules.0.sendTime', '09:00')
            ->assertJsonPath('data.schedules.0.sendToBot', false)
            ->assertJsonPath('data.botLinked', false);
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public static function invalidInputs(): array
    {
        return [
            'interval' => [['interval' => '2'], 'interval'],
            'time' => [['sendTime' => '25:30'], 'sendTime'],
            'timezone' => [['timezone' => 'unknown'], 'timezone'],
            'duplicate group' => [['groups' => ['@publicgroup', 'https://t.me/publicgroup']], 'groups.0'],
            'private invite' => [['groups' => ['https://t.me/+abcdef']], 'groups.0'],
            'two groups on one line' => [['groups' => ['@publicgroup @anothergroup']], 'groups.0'],
            'too many groups' => [['groups' => ['firstgroup', 'secondgroup', 'thirdgroup', 'fourthgroup']], 'groups'],
            'missing name' => [['name' => ' '], 'name'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_schedule_input(array $overrides, string $field): void
    {
        $this->actingAs(User::factory()->create())->postJson('/telegram/analytics/reports/schedules', $this->input($overrides))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('telegram_analytics_schedules', 0);
    }

    public function test_foreign_reports_and_schedules_are_inaccessible(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->postJson('/telegram/analytics/reports/schedules', $this->input())->assertCreated();
        $schedule = TelegramAnalyticsSchedule::query()->firstOrFail();
        $report = $this->report($schedule);
        $this->actingAs(User::factory()->create());
        $this->getJson('/telegram/analytics/reports')->assertJsonCount(0, 'data.schedules')->assertJsonCount(0, 'data.reports.data');
        $this->patchJson('/telegram/analytics/reports/schedules/'.$schedule->id, ['action' => 'pause'])->assertNotFound();
        $this->postJson('/telegram/analytics/reports/schedules/'.$schedule->id.'/run')->assertNotFound();
        $this->deleteJson('/telegram/analytics/reports/schedules/'.$schedule->id)->assertNotFound();
        $this->get('/telegram/analytics/reports/'.$report->id.'/view')->assertNotFound();
        $this->get('/telegram/analytics/reports/'.$report->id.'/download/json')->assertNotFound();
    }

    public function test_saved_report_is_viewable_and_downloadable_after_snapshot_expires(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/telegram/analytics/reports/schedules', $this->input())->assertCreated();
        $report = $this->report(TelegramAnalyticsSchedule::query()->firstOrFail());
        Cache::flush();
        $this->get('/telegram/analytics/reports/'.$report->id.'/view?locale=ru')
            ->assertOk()->assertSee('publicgroup')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/telegram/analytics/reports/'.$report->id.'/download/html')
            ->assertOk()->assertDownload();
        $this->getJson('/telegram/analytics/reports/'.$report->id.'/download/json')
            ->assertOk()->assertJsonPath('range.chatUsername', 'publicgroup')->assertDownload();
        $this->getJson('/telegram/analytics/reports')
            ->assertJsonPath('data.reports.data.0.chatUsername', 'publicgroup')
            ->assertJsonMissingPath('data.reports.data.0.data');
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public function test_pause_resume_and_manual_run_preserve_schedule(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/telegram/analytics/reports/schedules', $this->input())->assertCreated();
        $schedule = TelegramAnalyticsSchedule::query()->firstOrFail();
        $this->patchJson('/telegram/analytics/reports/schedules/'.$schedule->id, ['action' => 'pause'])->assertOk();
        $this->assertFalse($schedule->fresh()->enabled);
        $this->patchJson('/telegram/analytics/reports/schedules/'.$schedule->id, ['action' => 'resume'])->assertOk();
        $this->assertTrue($schedule->fresh()->enabled);
        $due = $schedule->fresh()->next_run_at;
        $this->postJson('/telegram/analytics/reports/schedules/'.$schedule->id.'/run')->assertAccepted();
        $this->assertDatabaseCount('telegram_analytics_reports', 2);
        $this->assertTrue($due->equalTo($schedule->fresh()->next_run_at));
        $this->deleteJson('/telegram/analytics/reports/schedules/'.$schedule->id)->assertOk();
        $this->assertSoftDeleted('telegram_analytics_schedules', ['id' => $schedule->id]);
    }

    public function test_unfinished_report_cannot_be_downloaded(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/telegram/analytics/reports/schedules', $this->input())->assertCreated();
        $report = $this->report(TelegramAnalyticsSchedule::query()->firstOrFail());
        $report->update(['status' => 'pending', 'data' => null, 'completed_at' => null]);
        $this->get('/telegram/analytics/reports/'.$report->id.'/view')->assertNotFound();
        $this->get('/telegram/analytics/reports/'.$report->id.'/download/json')->assertNotFound();
    }

    public function test_analytics_access_policy_applies_to_reports_tab_and_endpoints(): void
    {
        config(['access.plans.free' => [...config('access.plans.free'), 'telegram.analytics' => 0]]);
        $this->actingAs(User::factory()->create())->getJson('/telegram/analytics/reports')->assertForbidden();
        $this->postJson('/telegram/analytics/reports/schedules', $this->input())->assertForbidden();
        $this->getJson('/telegram?tab=reports')->assertForbidden();
    }

    public function test_guests_cannot_access_report_history(): void
    {
        $this->getJson('/telegram/analytics/reports')->assertUnauthorized();
    }

    private function input(array $overrides = []): array
    {
        return [...[
            'name' => 'Weekly groups', 'groups' => ['@PublicGroup', 'https://t.me/anothergroup/'],
            'interval' => '7', 'sendTime' => '09:00', 'timezone' => 'Europe/Moscow', 'sendToBot' => false,
        ], ...$overrides];
    }

    private function report(TelegramAnalyticsSchedule $schedule): TelegramAnalyticsReport
    {
        return TelegramAnalyticsReport::query()->create([
            'user_id' => $schedule->user_id, 'schedule_id' => $schedule->id, 'chat_username' => 'publicgroup',
            'scheduled_for' => now(), 'date_from' => now()->subWeek()->startOfDay(), 'date_to' => now()->subDay()->endOfDay(),
            'status' => 'completed', 'completed_at' => now(),
            'data' => ['range' => ['chatUsername' => 'publicgroup', 'label' => '02.10.2026 - 08.10.2026', 'periodDays' => 7], 'summary' => [], 'previousReport' => null],
        ]);
    }
}
