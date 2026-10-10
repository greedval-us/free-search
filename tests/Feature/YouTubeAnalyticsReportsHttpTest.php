<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\YouTubeAnalyticsReport;
use App\Models\YouTubeAnalyticsSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YouTubeAnalyticsReportsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 10, 9)->setTime(8, 0));
        Http::preventStrayRequests();
        config(['youtube_analytics_reports.queue.connection' => 'database', 'youtube_bot.enabled' => false]);
    }

    public function test_creates_normalized_schedule_without_charging_for_configuration(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/youtube/analytics/reports/schedules', $this->input())
            ->assertCreated();
        $schedule = YouTubeAnalyticsSchedule::query()->firstOrFail();
        $this->assertSame(['@publicchannel', '@anotherchannel'], $schedule->channels);
        $this->assertSame('7', $schedule->interval);
        $this->assertSame('09:00', $schedule->send_time);
        $this->assertSame($user->id, $schedule->user_id);
        $this->getJson('/youtube/analytics/reports')->assertOk()
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
            'duplicate channel' => [['channels' => ['@publicchannel', 'https://www.youtube.com/@publicchannel']], 'channels.0'],
            'private invite' => [['channels' => ['https://www.youtube.com/@+abcdef']], 'channels.0'],
            'two channels on one line' => [['channels' => ['@publicchannel @anotherchannel']], 'channels.0'],
            'too many channels' => [['channels' => ['firstchannel', 'secondchannel', 'thirdchannel', 'fourthchannel']], 'channels'],
            'missing name' => [['name' => ' '], 'name'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_schedule_input(array $overrides, string $field): void
    {
        $this->actingAs(User::factory()->create())->postJson('/youtube/analytics/reports/schedules', $this->input($overrides))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('youtube_analytics_schedules', 0);
    }

    public function test_foreign_reports_and_schedules_are_inaccessible(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->postJson('/youtube/analytics/reports/schedules', $this->input())->assertCreated();
        $schedule = YouTubeAnalyticsSchedule::query()->firstOrFail();
        $report = $this->report($schedule);
        $this->actingAs(User::factory()->create());
        $this->getJson('/youtube/analytics/reports')->assertJsonCount(0, 'data.schedules')->assertJsonCount(0, 'data.reports.data');
        $this->patchJson('/youtube/analytics/reports/schedules/'.$schedule->id, ['action' => 'pause'])->assertNotFound();
        $this->postJson('/youtube/analytics/reports/schedules/'.$schedule->id.'/run')->assertNotFound();
        $this->deleteJson('/youtube/analytics/reports/schedules/'.$schedule->id)->assertNotFound();
        $this->get('/youtube/analytics/reports/'.$report->id.'/view')->assertNotFound();
        $this->get('/youtube/analytics/reports/'.$report->id.'/download/json')->assertNotFound();
    }

    public function test_saved_report_is_viewable_and_downloadable_after_snapshot_expires(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/youtube/analytics/reports/schedules', $this->input())->assertCreated();
        $report = $this->report(YouTubeAnalyticsSchedule::query()->firstOrFail());
        Cache::flush();
        $this->get('/youtube/analytics/reports/'.$report->id.'/view?locale=ru')
            ->assertOk()->assertSee('publicchannel')->assertSee('Период публикации видео')
            ->assertSee('2026-10-02')->assertSee('2026-10-08')->assertSee('накопленные показатели')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/youtube/analytics/reports/'.$report->id.'/download/html')
            ->assertOk()->assertDownload();
        $this->getJson('/youtube/analytics/reports/'.$report->id.'/download/json')
            ->assertOk()->assertJsonPath('range.channelInput', '@publicchannel')->assertDownload();
        $this->getJson('/youtube/analytics/reports')
            ->assertJsonPath('data.reports.data.0.channelInput', '@publicchannel')
            ->assertJsonMissingPath('data.reports.data.0.data');
        $report->schedule->delete();
        $this->getJson('/youtube/analytics/reports/'.$report->id.'/download/json')
            ->assertOk()->assertJsonPath('totals.views', 456);
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public function test_pause_resume_and_manual_run_preserve_schedule(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/youtube/analytics/reports/schedules', $this->input())->assertCreated();
        $schedule = YouTubeAnalyticsSchedule::query()->firstOrFail();
        $this->patchJson('/youtube/analytics/reports/schedules/'.$schedule->id, ['action' => 'pause'])->assertOk();
        $this->assertFalse($schedule->fresh()->enabled);
        $this->patchJson('/youtube/analytics/reports/schedules/'.$schedule->id, ['action' => 'resume'])->assertOk();
        $this->assertTrue($schedule->fresh()->enabled);
        $due = $schedule->fresh()->next_run_at;
        $this->postJson('/youtube/analytics/reports/schedules/'.$schedule->id.'/run')->assertAccepted();
        $this->assertDatabaseCount('youtube_analytics_reports', 2);
        $this->assertTrue($due->equalTo($schedule->fresh()->next_run_at));
        $this->deleteJson('/youtube/analytics/reports/schedules/'.$schedule->id)->assertOk();
        $this->assertSoftDeleted('youtube_analytics_schedules', ['id' => $schedule->id]);
    }

    public function test_channel_ids_preserve_case_and_distinctness(): void
    {
        $ids = ['UCabcdefghijklmnopqrstuv', 'UCAbcdefghijklmnopqrstuv'];
        $this->actingAs(User::factory()->create())
            ->postJson('/youtube/analytics/reports/schedules', $this->input(['channels' => $ids]))
            ->assertCreated()->assertJsonPath('data.channels', $ids);
    }

    public function test_unavailable_metrics_remain_null_in_downloads_and_are_labelled_in_html(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/youtube/analytics/reports/schedules', $this->input())->assertCreated();
        $report = $this->report(YouTubeAnalyticsSchedule::query()->firstOrFail());
        $data = $report->data;
        $data['totals'] = ['videos' => 1, 'views' => 456, 'likes' => 12, 'comments' => null, 'engagementRate' => null];
        $data['methodology']['statisticsComplete'] = false;
        $data['methodology']['missingMetricCounts'] = ['likes' => 0, 'comments' => 1];
        $report->update(['data' => $data]);

        $this->get('/youtube/analytics/reports/'.$report->id.'/view?locale=ru')
            ->assertOk()->assertSee('не заменяются нулём')->assertSee('Нет данных');
        $this->getJson('/youtube/analytics/reports/'.$report->id.'/download/json')
            ->assertOk()->assertJsonPath('totals.comments', null)
            ->assertJsonPath('methodology.missingMetricCounts.comments', 1);
    }

    public function test_unfinished_report_cannot_be_downloaded(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/youtube/analytics/reports/schedules', $this->input())->assertCreated();
        $report = $this->report(YouTubeAnalyticsSchedule::query()->firstOrFail());
        $report->update(['status' => 'pending', 'data' => null, 'completed_at' => null]);
        $this->get('/youtube/analytics/reports/'.$report->id.'/view')->assertNotFound();
        $this->get('/youtube/analytics/reports/'.$report->id.'/download/json')->assertNotFound();
    }

    public function test_analytics_access_policy_applies_to_reports_tab_and_endpoints(): void
    {
        config(['access.plans.free' => [...config('access.plans.free'), 'youtube.analytics' => 0]]);
        $this->actingAs(User::factory()->create())->getJson('/youtube/analytics/reports')->assertForbidden();
        $this->postJson('/youtube/analytics/reports/schedules', $this->input())->assertForbidden();
        $this->getJson('/youtube?tab=reports')->assertForbidden();
    }

    public function test_guests_cannot_access_report_history(): void
    {
        $this->getJson('/youtube/analytics/reports')->assertUnauthorized();
    }

    private function input(array $overrides = []): array
    {
        return [...[
            'name' => 'Weekly channels', 'channels' => ['@PublicChannel', 'https://www.youtube.com/@anotherchannel/'],
            'interval' => '7', 'sendTime' => '09:00', 'timezone' => 'Europe/Moscow', 'sendToBot' => false,
        ], ...$overrides];
    }

    private function report(YouTubeAnalyticsSchedule $schedule): YouTubeAnalyticsReport
    {
        return YouTubeAnalyticsReport::query()->create([
            'user_id' => $schedule->user_id, 'schedule_id' => $schedule->id, 'channel_input' => '@publicchannel',
            'scheduled_for' => now(), 'date_from' => now()->subWeek()->startOfDay(), 'date_to' => now()->subDay()->endOfDay(),
            'status' => 'completed', 'completed_at' => now(),
            'data' => ['channel' => ['title' => 'Saved channel'], 'range' => ['channelInput' => '@publicchannel', 'dateFrom' => '2026-10-02', 'dateTo' => '2026-10-08', 'timezone' => 'Europe/Moscow', 'periodDays' => 7], 'totals' => ['videos' => 2, 'views' => 456], 'methodology' => ['metrics' => 'lifetime_at_collection']],
        ]);
    }
}
