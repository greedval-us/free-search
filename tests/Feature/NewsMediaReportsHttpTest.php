<?php

namespace Tests\Feature;

use App\Models\NewsMediaReportSchedule;
use App\Models\NewsMediaScheduledReport;
use App\Models\User;
use App\Modules\NewsMediaIntel\Application\Reports\Jobs\GenerateNewsMediaReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class NewsMediaReportsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        $this->travelTo(now()->setDate(2026, 10, 9)->setTime(8, 0));
        config(['news_media_reports.queue.connection' => 'news-media-reports-database',
            'telegram_bot.enabled' => false, 'inertia.ssr.enabled' => false]);
    }

    public function test_creates_owned_schedule_with_saved_analytics_filters_without_searching_or_spending_quota(): void
    {
        config(['news_media_reports.max_queries' => 10]);
        $this->actingAs(User::factory()->create())->postJson('/news-media-intel/reports/schedules', $this->input())
            ->assertCreated()->assertJsonPath('data.name', 'Brand research')
            ->assertJsonPath('data.queries', ['Acme products', 'market research'])
            ->assertJsonPath('data.domain', 'acme.com')->assertJsonPath('data.competitors', ['Beta'])
            ->assertJsonPath('data.searchOptions.timeRange', 'month')->assertJsonPath('data.searchOptions.language', 'en')
            ->assertJsonPath('data.searchOptions.engines', ['google', 'google news']);
        $schedule = NewsMediaReportSchedule::query()->sole();
        $this->assertEqualsCanonicalizing(['general', 'news'], $schedule->search_options['categories']);
        $this->getJson('/news-media-intel/reports')->assertOk()->assertJsonPath('data.maxQueries', 3)
            ->assertJsonPath('data.botLinked', false)->assertJsonPath('data.schedules.0.sendTime', '09:00')
            ->assertJsonPath('data.reports.total', 0)->assertJsonMissingPath('data.schedules.0.lease_token');
        Http::assertNothingSent();
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public static function invalidInputs(): array
    {
        return [
            [['name' => ' '], 'name'], [['queries' => []], 'queries'],
            [['queries' => ['one', 'two', 'three', 'four']], 'queries'],
            [['queries' => ['Acme', ' Acme ']], 'queries.0'],
            [['queries' => ['!google Acme']], 'queries.0'],
            [['queries' => ["Acme\nBeta"]], 'queries.0'], [['queries' => ['Acme :en']], 'queries.0'],
            [['queries' => ['x']], 'queries.0'], [['queries' => ['0' => 'Acme', 'other' => 'Beta']], 'queries'],
            [['interval' => '2'], 'interval'], [['sendTime' => '25:00'], 'sendTime'],
            [['timezone' => 'unknown'], 'timezone'], [['sendToBot' => 'perhaps'], 'sendToBot'],
            [['language' => 'unknown'], 'language'], [['timeRange' => '3days'], 'timeRange'],
            [['safeSearch' => 3], 'safeSearch'], [['maxPages' => 20], 'maxPages'],
            [['engines' => ['unknown']], 'engines.0'],
            [['brand' => 'Acme', 'competitors' => ['acme']], 'competitors'],
            [['competitors' => ['Beta', 'beta']], 'competitors.0'],
            [['domain' => 'https://user:secret@acme.com/'], 'domain'],
            [['domain' => '.acme.com'], 'domain'], [['domain' => '127.0.0.1'], 'domain'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_queries_and_schedule_options_before_any_external_search(array $overrides, string $field): void
    {
        $this->actingAs(User::factory()->create())->postJson('/news-media-intel/reports/schedules', $this->input($overrides))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('news_media_report_schedules', 0);
        Http::assertNothingSent();
    }

    public function test_pause_manual_run_resume_and_delete_preserve_due_time_and_saved_history(): void
    {
        Bus::fake();
        $this->actingAs(User::factory()->create())->postJson('/news-media-intel/reports/schedules', $this->input())->assertCreated();
        $schedule = NewsMediaReportSchedule::query()->sole();
        $due = $schedule->next_run_at;
        $this->patchJson('/news-media-intel/reports/schedules/'.$schedule->id, ['action' => 'pause'])->assertOk();
        $this->postJson('/news-media-intel/reports/schedules/'.$schedule->id.'/run')->assertAccepted();
        $this->assertTrue($due->equalTo($schedule->fresh()->next_run_at));
        $this->assertSame(2, $schedule->reports()->where('is_manual', true)->count());
        Bus::assertDispatched(GenerateNewsMediaReport::class);
        $this->travel(2)->days();
        $this->patchJson('/news-media-intel/reports/schedules/'.$schedule->id, ['action' => 'resume'])->assertOk();
        $this->assertTrue($schedule->fresh()->next_run_at->gt($due));
        $this->deleteJson('/news-media-intel/reports/schedules/'.$schedule->id)->assertOk();
        $this->assertSoftDeleted('news_media_report_schedules', ['id' => $schedule->id]);
        $this->getJson('/news-media-intel/reports')->assertJsonCount(0, 'data.schedules')->assertJsonPath('data.reports.total', 2);
        $this->patchJson('/news-media-intel/reports/schedules/'.$schedule->id, ['action' => 'resume'])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_persistent_report_reuses_existing_html_and_json_after_cache_expiry_and_schedule_deletion(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/news-media-intel/reports/schedules', $this->input())->assertCreated();
        $report = $this->savedReport(NewsMediaReportSchedule::query()->sole());
        Cache::flush();
        $this->get('/news-media-intel/reports/'.$report->id.'/view?locale=ru')->assertOk()
            ->assertSee('Новости и медиа: аналитика для SEO и маркетинга')->assertSee('Отчёт по расписанию')
            ->assertSee('&lt;script&gt;query&lt;/script&gt;', false)->assertDontSee('<script>', false)
            ->assertDontSee('href="javascript:', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/news-media-intel/reports/'.$report->id.'/download/html?locale=en')->assertOk()->assertDownload()
            ->assertSee('Scheduled report')->assertSee('News and media: SEO and marketing analytics');
        $this->getJson('/news-media-intel/reports/'.$report->id.'/download/json')->assertOk()->assertDownload()
            ->assertJsonPath('reportSchedule.metricsBasis', 'sample_at_check_time')->assertJsonPath('options.timeRange', 'month');
        $this->getJson('/news-media-intel/reports')->assertJsonPath('data.reports.total', 1)
            ->assertJsonMissingPath('data.reports.data.0.data')->assertJsonMissingPath('data.reports.data.0.lease_token');
        $report->schedule->delete();
        $this->get('/news-media-intel/reports/'.$report->id.'/view')->assertOk();
        $this->getJson('/news-media-intel/reports/'.$report->id.'/download/json')->assertOk();
        Http::assertNothingSent();
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public function test_foreign_schedules_reports_and_internal_state_are_not_accessible(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/news-media-intel/reports/schedules', $this->input())->assertCreated();
        $schedule = NewsMediaReportSchedule::query()->sole();
        $report = $this->savedReport($schedule);
        $this->actingAs(User::factory()->create());
        $this->getJson('/news-media-intel/reports')->assertJsonCount(0, 'data.schedules')->assertJsonPath('data.reports.total', 0);
        $this->patchJson('/news-media-intel/reports/schedules/'.$schedule->id, ['action' => 'pause'])->assertNotFound();
        $this->postJson('/news-media-intel/reports/schedules/'.$schedule->id.'/run')->assertNotFound();
        $this->deleteJson('/news-media-intel/reports/schedules/'.$schedule->id)->assertNotFound();
        $this->get('/news-media-intel/reports/'.$report->id.'/view')->assertNotFound();
        $this->getJson('/news-media-intel/reports/'.$report->id.'/download/json')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_guest_unverified_and_blocked_accounts_and_unfinished_reports_are_rejected(): void
    {
        $this->getJson('/news-media-intel/reports')->assertUnauthorized();
        $this->postJson('/news-media-intel/reports/schedules', $this->input())->assertUnauthorized();
        $this->actingAs(User::factory()->unverified()->create())->getJson('/news-media-intel/reports')->assertForbidden();
        $this->actingAs(User::factory()->create(['is_blocked' => true]))->postJson('/news-media-intel/reports/schedules', $this->input())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->postJson('/news-media-intel/reports/schedules', $this->input())->assertCreated();
        $report = $this->savedReport(NewsMediaReportSchedule::query()->sole());
        $report->update(['status' => 'pending', 'data' => null, 'completed_at' => null]);
        $this->get('/news-media-intel/reports/'.$report->id.'/view')->assertNotFound();
        $this->getJson('/news-media-intel/reports/'.$report->id.'/download/json')->assertNotFound();
        $this->getJson('/news-media-intel/reports/'.$report->id.'/download/pdf')->assertNotFound();
        Http::assertNothingSent();
    }

    private function input(array $overrides = []): array
    {
        return [...['name' => ' Brand research ', 'queries' => [' Acme products ', 'market research'],
            'brand' => 'Acme', 'competitors' => [' Beta '], 'domain' => 'HTTPS://ACME.COM/catalog',
            'language' => 'en', 'timeRange' => 'month', 'safeSearch' => 1, 'engines' => ['google', 'google news'], 'maxPages' => 1,
            'interval' => '7', 'sendTime' => '09:00', 'timezone' => 'Europe/Moscow', 'sendToBot' => false], ...$overrides];
    }

    private function savedReport(NewsMediaReportSchedule $schedule): NewsMediaScheduledReport
    {
        return $schedule->reports()->create([
            'user_id' => $schedule->user_id, 'query' => $schedule->queries[0], 'brand' => $schedule->brand,
            'competitors' => $schedule->competitors, 'domain' => $schedule->domain, 'search_options' => $schedule->search_options,
            'scheduled_for' => now(), 'status' => NewsMediaScheduledReport::COMPLETED, 'completed_at' => now(),
            'data' => ['query' => '<script>query</script>', 'checkedAt' => now()->toIso8601String(),
                'options' => $schedule->search_options, 'summary' => [], 'visibility' => ['domain' => $schedule->domain],
                'mentions' => [['title' => '<script>source</script>', 'snippet' => '<img src=x onerror=alert(1)>', 'link' => 'javascript:alert(1)', 'publishedAt' => '']],
                'reportSchedule' => ['scheduledFor' => now()->toIso8601String(), 'checkedAt' => now()->toIso8601String(),
                    'timezone' => 'Europe/Moscow', 'frequency' => '7', 'metricsBasis' => 'sample_at_check_time']],
        ]);
    }
}
