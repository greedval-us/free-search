<?php

namespace Tests\Feature;

use App\Models\SiteIntelReportSchedule;
use App\Models\SiteIntelScheduledReport;
use App\Models\User;
use App\Services\Access\SiteIntelReportAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SiteIntelReportsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        $this->travelTo(now()->setDate(2026, 10, 9)->setTime(8, 0));
        config(['site_intel_reports.queue.connection' => 'site-intel-reports-database', 'telegram_bot.enabled' => false, 'inertia.ssr.enabled' => false]);
    }

    public static function types(): array
    {
        return [['analytics', 'Аналитика сайта'], ['seo-audit', 'SEO-аудит']];
    }

    #[DataProvider('types')]
    public function test_creates_normalized_schedule_and_seo_options_without_spending_quota(string $type, string $label): void
    {
        $this->actingAs(User::factory()->create())->postJson('/site-intel/reports/schedules', $this->input($type))
            ->assertCreated()->assertJsonPath('data.targets', ['https://example.com/News?Tag=A', 'http://www.example.org/'])
            ->assertJsonPath('data.reportType', $type)->assertJsonPath('data.crawlLimit', 12)->assertJsonPath('data.platformType', 'content-site');
        $this->getJson('/site-intel/reports')->assertOk()->assertJsonPath('data.availableReportTypes', ['analytics', 'seo-audit'])
            ->assertJsonPath('data.maxTargets', 3)->assertJsonPath('data.schedules.0.sendTime', '09:00');
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public static function invalidInputs(): array
    {
        return [
            [['targets' => ['https://example.com/', 'example.com']], 'targets.0'],
            [['targets' => ['https://127.0.0.1/']], 'targets.0'],
            [['targets' => ['https://private.local/']], 'targets.0'],
            [['targets' => ['ftp://example.com/']], 'targets.0'],
            [['targets' => ['https://user:secret@example.com/']], 'targets.0'],
            [['targets' => ['https://example.com:8080/']], 'targets.0'],
            [['targets' => ['example.com another.org']], 'targets.0'],
            [['targets' => ['first.com', 'second.com', 'third.com', 'fourth.com']], 'targets'],
            [['reportType' => 'traffic'], 'reportType'], [['crawlLimit' => 21], 'crawlLimit'],
            [['platformType' => 'unknown'], 'platformType'], [['interval' => '2'], 'interval'],
            [['sendTime' => '25:00'], 'sendTime'], [['timezone' => 'unknown'], 'timezone'], [['name' => ' '], 'name'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_targets_options_and_schedule(array $overrides, string $field): void
    {
        $this->actingAs(User::factory()->create())->postJson('/site-intel/reports/schedules', $this->input('analytics', $overrides))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('site_intel_report_schedules', 0);
    }

    #[DataProvider('types')]
    public function test_saved_snapshot_uses_existing_html_and_json_after_cache_expiry_and_deletion(string $type, string $label): void
    {
        $this->actingAs(User::factory()->create())->postJson('/site-intel/reports/schedules', $this->input($type))->assertCreated();
        $report = $this->savedReport(SiteIntelReportSchedule::query()->sole());
        Cache::flush();
        $this->get('/site-intel/reports/'.$report->id.'/view?locale=ru')->assertOk()->assertSee($label)
            ->assertSee('Отчёт по расписанию')->assertSee('состояние сайта на момент проверки')
            ->assertSee('&lt;script&gt;test&lt;/script&gt;', false)->assertDontSee('<script>test</script>', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/site-intel/reports/'.$report->id.'/download/html')->assertOk()->assertDownload();
        $this->getJson('/site-intel/reports/'.$report->id.'/download/json')->assertOk()->assertDownload()
            ->assertJsonPath('reportSchedule.type', $type)->assertJsonPath('reportSchedule.metricsBasis', 'snapshot_at_check_time');
        $this->getJson('/site-intel/reports')->assertJsonPath('data.reports.data.0.reportType', $type)->assertJsonMissingPath('data.reports.data.0.data');
        $report->schedule->delete();
        $this->getJson('/site-intel/reports/'.$report->id.'/download/json')->assertOk();
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public function test_foreign_schedules_and_reports_are_not_accessible(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/site-intel/reports/schedules', $this->input())->assertCreated();
        $schedule = SiteIntelReportSchedule::query()->sole();
        $report = $this->savedReport($schedule);
        $this->actingAs(User::factory()->create());
        $this->getJson('/site-intel/reports')->assertJsonCount(0, 'data.schedules')->assertJsonCount(0, 'data.reports.data');
        $this->patchJson('/site-intel/reports/schedules/'.$schedule->id, ['action' => 'pause'])->assertNotFound();
        $this->postJson('/site-intel/reports/schedules/'.$schedule->id.'/run')->assertNotFound();
        $this->deleteJson('/site-intel/reports/schedules/'.$schedule->id)->assertNotFound();
        $this->get('/site-intel/reports/'.$report->id.'/view')->assertNotFound();
        $this->getJson('/site-intel/reports/'.$report->id.'/download/json')->assertNotFound();
    }

    public function test_seo_only_access_allows_tab_and_filters_history_and_creation_by_type(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['analytics', 'seo-audit'] as $type) {
            $this->postJson('/site-intel/reports/schedules', $this->input($type))->assertCreated();
            $this->savedReport(SiteIntelReportSchedule::query()->latest('id')->firstOrFail());
        }
        $analytics = SiteIntelScheduledReport::query()->where('report_type', 'analytics')->sole();
        config()->set('access.plans.free', ['site-intel.analytics' => 0, 'site-intel.seo-audit' => 5]);
        $this->get('/site-intel?tab=reports')->assertOk();
        $this->getJson('/site-intel/reports')->assertOk()->assertJsonPath('data.availableReportTypes', ['seo-audit'])
            ->assertJsonCount(1, 'data.reports.data')->assertJsonPath('data.reports.data.0.reportType', 'seo-audit');
        $this->postJson('/site-intel/reports/schedules', $this->input('analytics'))->assertForbidden();
        $this->postJson('/site-intel/reports/schedules', $this->input('seo-audit'))->assertCreated();
        $this->getJson('/site-intel/reports/'.$analytics->id.'/view')->assertForbidden();
        $this->getJson('/site-intel/reports/'.$analytics->id.'/download/json')->assertForbidden();
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public function test_pause_delete_remain_available_when_type_is_revoked_but_resume_and_run_do_not(): void
    {
        Bus::fake();
        $this->actingAs(User::factory()->create())->postJson('/site-intel/reports/schedules', $this->input('seo-audit'))->assertCreated();
        $schedule = SiteIntelReportSchedule::query()->sole();
        $due = $schedule->next_run_at;
        $this->postJson('/site-intel/reports/schedules/'.$schedule->id.'/run')->assertAccepted();
        $this->assertTrue($due->equalTo($schedule->fresh()->next_run_at));
        $this->assertSame(['analytics', 'seo-audit'], app(SiteIntelReportAccess::class)->availableTypes(auth()->user()));
        config()->set('access.plans.free', ['site-intel.analytics' => 0, 'site-intel.seo-audit' => 0]);
        $this->patchJson('/site-intel/reports/schedules/'.$schedule->id, ['action' => 'pause'])->assertOk();
        $this->assertFalse($schedule->fresh()->enabled);
        $this->patchJson('/site-intel/reports/schedules/'.$schedule->id, ['action' => 'resume'])->assertUnprocessable()->assertJsonPath('code', 'access_denied');
        $this->postJson('/site-intel/reports/schedules/'.$schedule->id.'/run')->assertUnprocessable()->assertJsonPath('code', 'access_denied');
        $this->deleteJson('/site-intel/reports/schedules/'.$schedule->id)->assertOk();
        $this->assertSoftDeleted('site_intel_report_schedules', ['id' => $schedule->id]);
    }

    public function test_guests_unfinished_reports_and_both_denied_capabilities_are_rejected(): void
    {
        $this->getJson('/site-intel/reports')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson('/site-intel/reports/schedules', $this->input())->assertCreated();
        $report = $this->savedReport(SiteIntelReportSchedule::query()->sole());
        $report->update(['status' => 'pending', 'data' => null, 'completed_at' => null]);
        $this->get('/site-intel/reports/'.$report->id.'/view')->assertNotFound();
        $this->getJson('/site-intel/reports/'.$report->id.'/download/json')->assertNotFound();
        config()->set('access.plans.free', ['site-intel.analytics' => 0, 'site-intel.seo-audit' => 0]);
        $this->getJson('/site-intel/reports')->assertForbidden();
        $this->getJson('/site-intel?tab=reports')->assertForbidden();
    }

    private function input(string $type = 'analytics', array $overrides = []): array
    {
        return [...['name' => 'Website checks', 'targets' => ['HTTPS://Example.COM/News?Tag=A#section', 'http://www.example.org'],
            'reportType' => $type, 'crawlLimit' => 12, 'platformType' => 'content-site', 'interval' => '7', 'sendTime' => '09:00',
            'timezone' => 'Europe/Moscow', 'sendToBot' => false], ...$overrides];
    }

    private function savedReport(SiteIntelReportSchedule $schedule): SiteIntelScheduledReport
    {
        return $schedule->reports()->create([
            'user_id' => $schedule->user_id, 'target_url' => 'https://example.com/', 'report_type' => $schedule->report_type,
            'crawl_limit' => $schedule->crawl_limit, 'platform_type' => $schedule->platform_type,
            'scheduled_for' => now(), 'date_from' => now()->startOfDay(), 'date_to' => now(),
            'status' => SiteIntelScheduledReport::COMPLETED, 'completed_at' => now(),
            'data' => ['target' => ['url' => 'https://example.com/<script>test</script>', 'domain' => 'example.com', 'finalUrl' => 'https://example.com/<script>test</script>'],
                'checkedAt' => '2026-10-09T08:00:00+00:00', 'overview' => ['overallScore' => 88],
                'reportSchedule' => ['type' => $schedule->report_type, 'targetUrl' => 'https://example.com/',
                    'scheduledFor' => '2026-10-09T06:00:00+00:00', 'checkedAt' => '2026-10-09T08:00:00+00:00',
                    'timezone' => 'Europe/Moscow', 'frequency' => '7', 'metricsBasis' => 'snapshot_at_check_time']],
        ]);
    }
}
