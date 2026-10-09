<?php

namespace Tests\Feature;

use App\Models\NewsMediaScheduledReport;
use App\Models\User;
use App\Modules\NewsMediaIntel\Application\Contracts\SearxngSearchClientInterface;
use App\Modules\NewsMediaIntel\Application\Reports\Events\NewsMediaReportCompleted;
use App\Modules\NewsMediaIntel\Application\Reports\Jobs\GenerateNewsMediaReport;
use App\Modules\NewsMediaIntel\Application\Reports\ReportException;
use App\Modules\NewsMediaIntel\Application\Reports\ReportGenerator;
use App\Modules\NewsMediaIntel\Application\Reports\ReportScheduleService;
use App\Modules\NewsMediaIntel\Application\Reports\ReportSourceBuilder;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchResultDTO;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NewsMediaScheduledReportSourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['news_media_reports.queue.connection' => 'news-media-reports-database']);
        Http::preventStrayRequests();
    }

    public function test_report_uses_existing_analytics_with_frozen_filters_and_preserves_complete_payload(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
        $user = User::factory()->create();
        $schedule = app(ReportScheduleService::class)->create($user, $this->input([
            'brand' => 'Acme', 'domain' => 'https://Example.org/Path', 'interval' => '3',
            'search_options' => ['language' => 'en', 'timeRange' => 'week', 'safeSearch' => 2,
                'engines' => ['google', 'google news'], 'maxPages' => 2],
        ]));
        Bus::fake([GenerateNewsMediaReport::class]);
        app(ReportScheduleService::class)->runNow($user, $schedule->id);
        $report = NewsMediaScheduledReport::query()->firstOrFail();
        $schedule->update(['brand' => 'Changed', 'competitors' => ['Changed rival'], 'domain' => 'other.example',
            'search_options' => ['language' => 'ru', 'timeRange' => 'day', 'safeSearch' => 0, 'maxPages' => 1, 'engines' => []]]);
        $calls = [];
        $this->mock(SearxngSearchClientInterface::class)->shouldReceive('search')->twice()->andReturnUsing(
            static function (string $query, NewsSearchOptionsDTO $options, float $deadline) use (&$calls): NewsSearchResultDTO {
                $calls[] = ['query' => $query, 'options' => $options->toArray(), 'deadline' => $deadline];
                $category = $options->categories[0];

                return new NewsSearchResultDTO([
                    new NewsMentionDTO(source: 'Example', title: 'Acme gains', snippet: 'Acme growth',
                        link: 'https://example.org/'.$category, publishedAt: '2026-10-08T12:00:00Z',
                        engines: $options->engines, category: $category, position: 1),
                ], ['pagesRequested' => 1, 'pagesLoaded' => 1, 'truncated' => false],
                    suggestions: ['How to use Acme?'], corrections: ['Acme'], answers: ['Answer'],
                    infoboxes: [['title' => 'Acme', 'content' => 'Overview', 'urls' => [], 'attributes' => []]]);
            }
        );

        $result = app(ReportSourceBuilder::class)->build($report);

        $this->assertSame(['Brand research', 'Brand research'], array_column($calls, 'query'));
        $this->assertSame(['general'], $calls[0]['options']['categories']);
        $this->assertSame(['news'], $calls[1]['options']['categories']);
        $this->assertSame(['google'], $calls[0]['options']['engines']);
        $this->assertSame(['google news'], $calls[1]['options']['engines']);
        foreach ($calls as $call) {
            $this->assertSame('en', $call['options']['language']);
            $this->assertSame('week', $call['options']['timeRange']);
            $this->assertSame(2, $call['options']['safeSearch']);
            $this->assertSame(2, $call['options']['maxPages']);
        }
        $this->assertSame('Acme', $result['brandComparison']['entities'][0]['name']);
        $this->assertSame('example.org', $result['visibility']['domain']);
        $this->assertSame(1, $result['visibility']['domainMatches']);
        $this->assertSame(['How to use Acme?'], $result['suggestions']);
        $this->assertSame(['Answer'], $result['answers']);
        $this->assertCount(1, $result['infoboxes']);
        $this->assertSame([
            'scheduledFor' => '2026-10-09T12:00:00+00:00', 'checkedAt' => '2026-10-09T12:00:00+00:00',
            'timezone' => 'Europe/Moscow', 'frequency' => '3', 'metricsBasis' => 'sample_at_check_time',
        ], $result['reportSchedule']);
        $this->assertArrayNotHasKey('range', $result);
        $this->assertArrayNotHasKey('reportExpiresAt', $result);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 1);
        Http::assertNothingSent();
    }

    public function test_explicit_all_time_remains_empty_when_server_default_is_month(): void
    {
        config(['osint.news_media_intel.searxng.time_range' => 'month']);
        $schedule = app(ReportScheduleService::class)->create(User::factory()->create(), $this->input([
            'search_options' => ['timeRange' => ''],
        ]));

        $this->assertSame('', $schedule->search_options['timeRange']);
        $this->assertSame(['general', 'news'], $schedule->search_options['categories']);
        Http::assertNothingSent();
    }

    public static function invalidParameters(): array
    {
        return [
            'no queries' => [['queries' => []], 'invalid_queries'],
            'too many' => [['queries' => ['One', 'Two', 'Three', 'Four']], 'invalid_queries'],
            'duplicate' => [['queries' => ['Acme', ' ACME ']], 'invalid_queries'],
            'routing' => [['queries' => ['Acme !google']], 'invalid_queries'],
            'language routing' => [['queries' => ['Acme :en']], 'invalid_queries'],
            'control query' => [['queries' => ["Acme\nproduct"]], 'invalid_queries'],
            'query array' => [['queries' => [['nested']]], 'invalid_queries'],
            'non-list queries' => [['queries' => ['other' => 'Acme']], 'invalid_queries'],
            'short query' => [['queries' => ['x']], 'invalid_queries'],
            'long query' => [['queries' => [str_repeat('я', 181)]], 'invalid_queries'],
            'short brand' => [['brand' => 'x'], 'invalid_options'],
            'control brand' => [['brand' => "Acme\nbrand"], 'invalid_options'],
            'same competitor' => [['brand' => 'Acme', 'competitors' => ['acme']], 'invalid_options'],
            'duplicate competitor' => [['competitors' => ['Rival', 'RIVAL']], 'invalid_options'],
            'many competitors' => [['competitors' => ['One', 'Two', 'Three', 'Four']], 'invalid_options'],
            'localhost' => [['domain' => 'localhost'], 'invalid_options'],
            'public IP' => [['domain' => '8.8.8.8'], 'invalid_options'],
            'leading domain dot' => [['domain' => '.example.org'], 'invalid_options'],
            'credentials' => [['domain' => 'https://secret@example.org'], 'invalid_options'],
            'unknown language' => [['search_options' => ['language' => 'other']], 'invalid_options'],
            'bad period' => [['search_options' => ['timeRange' => '3']], 'invalid_options'],
            'bad safe search' => [['search_options' => ['safeSearch' => 3]], 'invalid_options'],
            'fractional pages' => [['search_options' => ['maxPages' => 1.5]], 'invalid_options'],
            'many pages' => [['search_options' => ['maxPages' => 99]], 'invalid_options'],
            'unknown engine' => [['search_options' => ['engines' => ['internal']]], 'invalid_options'],
            'duplicate engine' => [['search_options' => ['engines' => ['google', 'google']]], 'invalid_options'],
            'non-list engines' => [['search_options' => ['engines' => ['other' => 'google']]], 'invalid_options'],
            'options scalar' => [['search_options' => 'bad'], 'invalid_options'],
            'blank name' => [['name' => ' '], 'invalid_options'],
            'bad frequency' => [['interval' => '2'], 'invalid_interval'],
            'bad time' => [['send_time' => '25:00'], 'invalid_time'],
            'bad timezone' => [['timezone' => 'Moscow'], 'invalid_timezone'],
        ];
    }

    #[DataProvider('invalidParameters')]
    public function test_application_boundary_rejects_invalid_parameters_before_persistence_or_search(array $override, string $reason): void
    {
        try {
            app(ReportScheduleService::class)->create(User::factory()->create(), $this->input($override));
            $this->fail('Invalid parameters must be rejected.');
        } catch (ReportException $exception) {
            $this->assertSame($reason, $exception->reason);
        }

        $this->assertDatabaseCount('news_media_report_schedules', 0);
        Http::assertNothingSent();
    }

    public function test_corrupted_saved_search_options_fail_permanently_without_external_request(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
        $user = User::factory()->create();
        $schedule = app(ReportScheduleService::class)->create($user, $this->input());
        Bus::fake([GenerateNewsMediaReport::class]);
        Event::fake([NewsMediaReportCompleted::class]);
        app(ReportScheduleService::class)->runNow($user, $schedule->id);
        $report = NewsMediaScheduledReport::query()->firstOrFail();
        $report->update(['search_options' => ['engines' => ['unknown']]]);
        $this->mock(SearxngSearchClientInterface::class)->shouldNotReceive('search');

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(NewsMediaScheduledReport::FAILED, $report->fresh()->status);
        $this->assertSame('invalid_options', $report->fresh()->error_code);
        $this->assertSame(1, $report->fresh()->attempt_count);
        $this->assertNull($report->fresh()->data);
        Event::assertNotDispatched(NewsMediaReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 1);
        Http::assertNothingSent();
    }

    private function input(array $override = []): array
    {
        return [...['name' => 'News report', 'queries' => ['Brand research'], 'brand' => '', 'competitors' => [], 'domain' => '',
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow'], ...$override];
    }
}
