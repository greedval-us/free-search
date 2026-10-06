<?php

namespace Tests\Feature\Monitoring;

use App\Jobs\Monitoring\BuildMonitoringReport;
use App\Jobs\Monitoring\CollectMonitoringSource;
use App\Jobs\Monitoring\ExportMonitoringReport;
use App\Jobs\Monitoring\ValidateMonitoringSource;
use App\Models\MonitoringProject;
use App\Models\MonitoringSource;
use App\Models\TelegramTrackingSource;
use App\Models\User;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\Telegram\Tracking\TrackingException;
use App\Services\Monitoring\MonitoringCollector;
use App\Services\Monitoring\MonitoringExports;
use App\Services\Monitoring\MonitoringManager;
use App\Services\Monitoring\MonitoringReports;
use App\Services\Monitoring\MonitoringScheduler;
use App\Services\Monitoring\MonitoringSummary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeTrackingGateway;
use Tests\TestCase;

final class MonitoringReliabilityTest extends TestCase
{
    use RefreshDatabase;

    private FakeTrackingGateway $gateway;

    private ?User $owner = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-06 09:00:00', 'UTC'));
        Http::preventStrayRequests();
        Bus::fake([ValidateMonitoringSource::class, CollectMonitoringSource::class, BuildMonitoringReport::class, ExportMonitoringReport::class]);
        Storage::fake('private');
        config(['telegram_bot.enabled' => false, 'monitoring.report_wait_seconds' => 0,
            'monitoring.initial_lookback_days' => 1, 'telegram_tracking.page_size' => 100]);
        $this->gateway = new FakeTrackingGateway;
        $this->app->instance(TrackingGateway::class, new class($this->gateway) implements TrackingGateway
        {
            public function __construct(private FakeTrackingGateway $fake) {}

            public function resolve(array $groups, ?string $keyword = null): array
            {
                return array_map(static function ($source) {
                    $source['peer_id'] = '-100'.crc32($source['username']);

                    return $source;
                }, $this->fake->resolve($groups, $keyword));
            }

            public function fetch(TelegramTrackingSource $source): array
            {
                return $this->fake->fetch($source);
            }
        });
    }

    #[DataProvider('truncationLimits')]
    public function test_truncated_platform_page_is_partial_and_does_not_claim_complete_report_coverage(string $limit, string $warning): void
    {
        config(['monitoring.'.$limit => 2]);
        $project = $this->project();
        $source = $this->source($project);
        $this->gateway->pages = [[$this->message(12), $this->message(11), $this->message(10)]];
        app(MonitoringScheduler::class)->tick();
        $collection = $source->collections()->sole();
        app(MonitoringCollector::class)->collect($collection->id, $project->user_id);
        $this->assertSame('partial', $collection->fresh()->status);
        $this->assertContains($warning, $collection->fresh()->warnings);
        $this->assertDatabaseCount('monitoring_materials', 2);
        $report = app(MonitoringManager::class)->requestReport($project->fresh(), 'day', (string) Str::uuid());
        app(MonitoringReports::class)->build($report->id, $project->user_id);
        $this->assertSame('partial', $report->fresh()->status);
        $this->assertSame(2, $report->fresh()->summary['count']);
        $this->assertFalse($report->fresh()->coverage[0]['complete']);
        $this->assertNotEmpty($report->fresh()->coverage[0]['gaps']);
        Bus::assertDispatched(CollectMonitoringSource::class, fn ($job) => $job->collectionId === $collection->id);
    }

    public static function truncationLimits(): array
    {
        return [
            'monitoring page lower than platform page' => ['page_size', 'page_item_limit'],
            'window cap lower than platform page' => ['max_items_per_window', 'collection_limit'],
        ];
    }

    #[DataProvider('inactiveProjects')]
    public function test_inactive_due_work_does_not_starve_active_schedule_or_source_with_one_row_tick_budget(string $reason): void
    {
        $inactive = $this->project();
        $inactiveSource = $this->source($inactive, 'inactivegroup');
        $inactiveSchedule = app(MonitoringManager::class)->saveSchedule($inactive->fresh(), ['period' => 'day', 'timezone' => 'UTC', 'time' => '09:00']);
        $inactiveSchedule->update(['next_run_at' => now()->subDays(3)]);
        $inactiveSource->update(['next_collect_at' => now()->subDays(3)]);
        if ($reason === 'blocked') {
            $inactive->user->update(['is_blocked' => true]);
        } else {
            app(MonitoringManager::class)->lifecycle($inactive->fresh(), 'paused');
        }
        $active = $this->project(User::factory()->create());
        $activeSource = $this->source($active, 'activegroup');
        $activeSchedule = app(MonitoringManager::class)->saveSchedule($active->fresh(), ['period' => 'day', 'timezone' => 'UTC', 'time' => '09:00']);
        $activeSchedule->update(['next_run_at' => now()->subDay()]);
        config(['monitoring.tick_limit' => 1]);

        app(MonitoringScheduler::class)->tick();

        $this->assertSame(0, $inactiveSource->collections()->count());
        $this->assertSame(0, $inactive->reports()->count());
        $collection = $activeSource->collections()->sole();
        $report = $active->reports()->sole();
        $this->assertTrue($activeSchedule->fresh()->next_run_at->isFuture());
        Bus::assertDispatched(CollectMonitoringSource::class, fn ($job) => $job->collectionId === $collection->id);
        Bus::assertDispatched(BuildMonitoringReport::class, fn ($job) => $job->reportId === $report->id);
    }

    public static function inactiveProjects(): array
    {
        return [['blocked'], ['paused']];
    }

    public function test_terminal_export_failure_does_not_starve_new_reports(): void
    {
        $project = $this->project();
        $failed = app(MonitoringManager::class)->requestReport($project, 'day', (string) Str::uuid());
        $failed->update(['status' => 'completed', 'file_status' => 'failed', 'delivery_status' => null]);
        $pending = app(MonitoringManager::class)->requestReport($project, 'day', (string) Str::uuid());
        $pending->update(['dispatched_at' => null]);
        Bus::fake([BuildMonitoringReport::class, ExportMonitoringReport::class]);
        config(['monitoring.tick_limit' => 1]);

        app(MonitoringScheduler::class)->tick();

        Bus::assertDispatched(BuildMonitoringReport::class, fn ($job) => $job->reportId === $pending->id);
        Bus::assertNotDispatched(ExportMonitoringReport::class);
        $this->assertSame('failed', $failed->fresh()->file_status);
    }

    public function test_expired_collection_leases_eventually_fail_without_committing_materials_or_retrying_forever(): void
    {
        config(['monitoring.max_attempts' => 3]);
        $project = $this->project();
        $source = $this->source($project);
        app(MonitoringScheduler::class)->tick();
        $collection = $source->collections()->sole();
        $collection->update(['status' => 'running', 'lease_token' => (string) Str::uuid(), 'lease_until' => now()->subSecond()]);
        $this->gateway->pages = [[$this->message()], [$this->message()]];
        $this->gateway->onHistory = function () use ($collection): void {
            // Simulate a timed-out worker losing its lease before it can commit its page.
            $collection->fresh()->update(['lease_until' => now()->subSecond()]);
        };
        app(MonitoringCollector::class)->collect($collection->id, $project->user_id);
        $this->assertSame(1, $collection->fresh()->attempts);
        app(MonitoringCollector::class)->collect($collection->id, $project->user_id);
        $this->assertSame(2, $collection->fresh()->attempts);
        app(MonitoringCollector::class)->collect($collection->id, $project->user_id);
        app(MonitoringCollector::class)->collect($collection->id, $project->user_id);

        $this->assertSame('failed', $collection->fresh()->status);
        $this->assertSame('worker_timeout', $collection->fresh()->error);
        $this->assertSame('error', $source->fresh()->status);
        $this->assertCount(2, $this->gateway->requests);
        $this->assertDatabaseCount('monitoring_materials', 0);
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    #[DataProvider('expiredReportOperations')]
    public function test_report_generation_and_export_stop_after_repeated_expired_leases(string $operation): void
    {
        config(['monitoring.max_attempts' => 3]);
        $project = $this->project();
        $report = app(MonitoringManager::class)->requestReport($project, 'day', (string) Str::uuid());
        $report->update(['status' => $operation === 'export' ? 'completed' : 'building',
            'file_status' => $operation === 'export' ? 'working' : 'pending',
            'attempts' => 2, 'lease_token' => (string) Str::uuid(), 'lease_until' => now()->subSecond()]);
        if ($operation === 'export') {
            app(MonitoringExports::class)->prepare($report->id, $project->user_id);
            $this->assertSame('completed', $report->fresh()->status);
            $this->assertSame('failed', $report->fresh()->file_status);
        } else {
            app(MonitoringReports::class)->build($report->id, $project->user_id);
            $this->assertSame('failed', $report->fresh()->status);
        }
        $this->assertSame('worker_timeout', $report->fresh()->error);
        $this->assertNull($report->fresh()->lease_token);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public static function expiredReportOperations(): array
    {
        return [['generation'], ['export']];
    }

    public function test_stale_schedule_instance_advances_revision_and_cancels_obsolete_queued_report(): void
    {
        $project = $this->project();
        $manager = app(MonitoringManager::class);
        $schedule = $manager->saveSchedule($project, ['period' => 'day', 'timezone' => 'UTC', 'time' => '09:00']);
        $stale = $schedule->fresh();
        $current = $manager->saveSchedule($project, ['period' => 'day', 'timezone' => 'UTC', 'time' => '10:00'], $schedule);
        $queued = $manager->makeReport($project, 'day', hash('sha256', 'revision-test'), CarbonImmutable::now('UTC'), $current);
        $next = $manager->saveSchedule($project, ['period' => 'day', 'timezone' => 'UTC', 'time' => '11:00', 'anchor_date' => null], $stale);

        $this->assertSame(3, $next->generation);
        $this->assertSame('11:00', $next->time);
        $this->assertNotNull($next->anchor_date);
        $this->assertSame('cancelled', $queued->fresh()->status);
        $this->assertSame(2, $queued->fresh()->configuration['schedule_generation']);
    }

    public function test_more_than_four_hundred_days_of_downtime_creates_only_latest_due_report_and_exact_skip_count(): void
    {
        $project = $this->project();
        $schedule = app(MonitoringManager::class)->saveSchedule($project, ['period' => 'day', 'timezone' => 'UTC', 'time' => '09:00']);
        $schedule->update(['next_run_at' => CarbonImmutable::parse('2025-05-24 09:00:00', 'UTC')]);
        app(MonitoringScheduler::class)->tick();
        app(MonitoringScheduler::class)->tick();

        $report = $project->reports()->sole();
        $this->assertSame('2026-10-05T00:00:00+00:00', $report->start_at->toIso8601String());
        $this->assertSame('2026-10-06T00:00:00+00:00', $report->end_at->toIso8601String());
        $this->assertSame('2026-10-06T09:00:00+00:00', $schedule->fresh()->last_run_at->toIso8601String());
        $this->assertSame('2026-10-07T09:00:00+00:00', $schedule->fresh()->next_run_at->toIso8601String());
        $this->assertSame(500, $schedule->fresh()->missed_runs);
        Bus::assertDispatched(BuildMonitoringReport::class, fn ($job) => $job->reportId === $report->id);
    }

    public function test_disabling_catch_up_still_generates_the_current_due_occurrence(): void
    {
        config(['monitoring.catch_up_reports' => 0]);
        $project = $this->project();
        $schedule = app(MonitoringManager::class)->saveSchedule($project, ['period' => 'day', 'timezone' => 'UTC', 'time' => '09:00']);
        $schedule->update(['next_run_at' => CarbonImmutable::parse('2026-10-06 09:00:00', 'UTC')]);
        app(MonitoringScheduler::class)->tick();
        app(MonitoringScheduler::class)->tick();

        $this->assertSame(1, $project->reports()->count());
        $this->assertSame(0, $schedule->fresh()->missed_runs);
        $this->assertTrue($schedule->fresh()->next_run_at->isFuture());
    }

    public function test_disabling_catch_up_skips_backlog_and_counts_every_missed_occurrence(): void
    {
        config(['monitoring.catch_up_reports' => 0]);
        $project = $this->project();
        $schedule = app(MonitoringManager::class)->saveSchedule($project, ['period' => 'day', 'timezone' => 'UTC', 'time' => '09:00']);
        $schedule->update(['next_run_at' => CarbonImmutable::parse('2026-10-02 09:00:00', 'UTC')]);
        app(MonitoringScheduler::class)->tick();
        app(MonitoringScheduler::class)->tick();

        $this->assertSame(0, $project->reports()->count());
        $this->assertSame(5, $schedule->fresh()->missed_runs);
        $this->assertTrue($schedule->fresh()->next_run_at->isFuture());
    }

    #[DataProvider('authorPlatforms')]
    public function test_telegram_author_filter_keeps_other_platforms_and_requires_matching_telegram_sender(string $platform, string $author, bool $matches): void
    {
        $project = new MonitoringProject(['mode' => 'overview', 'filters' => ['author' => '123']]);
        $this->assertSame($matches, app(MonitoringCollector::class)->matches($project,
            ['platform' => $platform, 'title' => '', 'text' => 'Research update', 'author' => $author]));
    }

    public static function authorPlatforms(): array
    {
        return [['telegram', '123', true], ['telegram', '456', false], ['youtube', 'Channel', true],
            ['bluesky', 'profile.bsky.social', true], ['mastodon', 'user@mastodon.social', true], ['news', 'example.com', true]];
    }

    public function test_summary_preserves_distinct_video_query_identity_and_has_saved_topic_links_and_timeline(): void
    {
        $summary = app(MonitoringSummary::class);
        $state = [];
        foreach ([
            ['id' => 1, 'title' => 'Research launches a first discovery', 'url' => 'https://www.youtube.com/watch?v=video_one', 'published_at' => '2026-10-05T12:00:00Z'],
            ['id' => 2, 'title' => 'Research announces a second discovery', 'url' => 'https://www.youtube.com/watch?v=video_two', 'published_at' => '2026-10-05T13:00:00Z'],
        ] as $item) {
            $summary->accumulate($state, $item + ['platform' => 'youtube', 'text' => 'Research discovery progress'], 'UTC');
        }
        $result = $summary->finish($state);

        $this->assertSame(2, $result['group_count']);
        $urls = array_merge(...array_column($result['bullets'], 'urls'));
        $this->assertContains('https://www.youtube.com/watch?v=video_one', $urls);
        $this->assertContains('https://www.youtube.com/watch?v=video_two', $urls);
        $research = collect($result['recurring_topics'])->firstWhere('term', 'research');
        $this->assertSame(2, $research['count']);
        $this->assertCount(2, $research['urls']);
        $this->assertSame(['2026-10-05' => 2], $result['timeline']);
    }

    public function test_transient_validation_failure_retries_without_manual_source_action(): void
    {
        $project = $this->project();
        $source = app(MonitoringManager::class)->addSource($project, ['platform' => 'telegram', 'input' => 'publicgroup']);
        $this->gateway->failure = new TrackingException('temporary_network_failure');
        $collector = app(MonitoringCollector::class);
        $collector->validate($source->id, $project->user_id, $source->generation, $source->fresh('project')->project->generation);
        $this->assertSame('pending', $source->fresh()->status);
        $this->assertTrue($source->fresh()->next_collect_at->isFuture());
        $this->gateway->failure = null;
        $this->travel(2)->minutes();
        $collector->validate($source->id, $project->user_id, $source->generation, $source->fresh('project')->project->generation);
        $this->assertSame('ready', $source->fresh()->status);
        $this->assertSame(0, $source->fresh()->attempts);
    }

    private function project(?User $user = null): MonitoringProject
    {
        $user ??= $this->owner ??= User::factory()->create();

        return app(MonitoringManager::class)->create($user, ['name' => 'Reliability project', 'timezone' => 'UTC', 'language' => 'en']);
    }

    private function source(MonitoringProject $project, string $input = 'publicgroup'): MonitoringSource
    {
        $source = app(MonitoringManager::class)->addSource($project, ['platform' => 'telegram', 'input' => $input]);
        app(MonitoringCollector::class)->validate($source->id, $project->user_id, $source->generation, $source->fresh('project')->project->generation);

        return $source->fresh('project');
    }

    private function message(int $id = 10): array
    {
        return ['_' => 'message', 'id' => $id, 'date' => CarbonImmutable::parse('2026-10-05 12:00:00', 'UTC')->timestamp,
            'message' => 'Research update '.$id, 'from_id' => ['user_id' => 123]];
    }
}
