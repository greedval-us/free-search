<?php

namespace Tests\Feature\Monitoring;

use App\Jobs\Monitoring\BuildMonitoringReport;
use App\Jobs\Monitoring\CollectMonitoringSource;
use App\Jobs\Monitoring\ExportMonitoringReport;
use App\Jobs\Monitoring\ValidateMonitoringSource;
use App\Models\FeatureUsageDaily;
use App\Models\MonitoringCollection;
use App\Models\MonitoringMaterial;
use App\Models\MonitoringProject;
use App\Models\MonitoringReport;
use App\Models\MonitoringSource;
use App\Models\ParserRun;
use App\Models\TelegramTrackingSource;
use App\Models\User;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\Telegram\Tracking\TrackingException;
use App\Services\Monitoring\MonitoringCollector;
use App\Services\Monitoring\MonitoringExports;
use App\Services\Monitoring\MonitoringManager;
use App\Services\Monitoring\MonitoringReports;
use App\Services\Monitoring\MonitoringScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\FakeTrackingGateway;
use Tests\TestCase;

class MonitoringWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private FakeTrackingGateway $gateway;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-10-06 09:00:00', 'UTC'));
        Http::preventStrayRequests();
        Bus::fake([ValidateMonitoringSource::class, CollectMonitoringSource::class, BuildMonitoringReport::class, ExportMonitoringReport::class]);
        Storage::fake('private');
        config(['telegram_bot.enabled' => false, 'monitoring.report_wait_seconds' => 0, 'monitoring.initial_lookback_days' => 1]);
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
        $this->user = User::factory()->create();
    }

    private function project(array $extra = []): MonitoringProject
    {
        return app(MonitoringManager::class)->create($this->user, array_replace(['name' => 'News project', 'timezone' => 'UTC', 'language' => 'en'], $extra));
    }

    private function source(MonitoringProject $project, string $input = 'publicgroup'): MonitoringSource
    {
        $source = app(MonitoringManager::class)->addSource($project, ['platform' => 'telegram', 'input' => $input]);
        $source = $source->fresh('project');
        app(MonitoringCollector::class)->validate($source->id, $this->user->id, $source->generation, $source->project->generation);

        return $source->fresh('project');
    }

    private function collect(MonitoringSource $source, array $messages = []): MonitoringCollection
    {
        $this->gateway->pages = [$messages];
        app(MonitoringScheduler::class)->tick();
        $collection = $source->collections()->latest('id')->firstOrFail();
        app(MonitoringCollector::class)->collect($collection->id, $this->user->id);

        return $collection->fresh();
    }

    private function message(int $id = 10, string $text = 'Publication'): array
    {
        return ['_' => 'message', 'id' => $id, 'date' => CarbonImmutable::parse('2026-10-05 12:00:00', 'UTC')->timestamp,
            'message' => $text, 'from_id' => ['user_id' => 123], 'views' => 8];
    }

    private function report(MonitoringProject $project): MonitoringReport
    {
        $report = app(MonitoringManager::class)->requestReport($project->fresh(), 'day', (string) Str::uuid());
        app(MonitoringReports::class)->build($report->id, $this->user->id);

        return $report->fresh();
    }

    public function test_telegram_end_to_end_saved_report_and_private_files_are_immutable(): void
    {
        $project = $this->project();
        $source = $this->source($project);
        $this->collect($source, [$this->message(text: '=HYPERLINK("evil")')]);
        $report = $this->report($project);
        $this->assertSame('completed', $report->status);
        $this->assertSame(1, $report->summary['count']);
        app(MonitoringExports::class)->prepare($report->id, $this->user->id);
        $report->refresh();
        $this->assertSame('ready', $report->file_status);
        $json = app(MonitoringExports::class)->artifact($report, 'json');
        $saved = json_decode(file_get_contents($json['path']), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('free-search.monitoring.v1', $saved['schema']);
        $this->assertSame('=HYPERLINK("evil")', $saved['materials'][0]['text']);
        $xlsx = app(MonitoringExports::class)->artifact($report, 'xlsx');
        $book = IOFactory::load($xlsx['path']);
        $this->assertSame(DataType::TYPE_STRING, $book->getSheet(1)->getCell('F2')->getDataType());
        MonitoringMaterial::query()->update(['text' => 'Edited later']);
        app(MonitoringManager::class)->removeSource($source);
        $this->actingAs($this->user)->get('/monitoring/reports/'.$report->id.'/download/json')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('=HYPERLINK("evil")', $report->items()->first()->snapshot['text']);
        $this->assertSame($saved, json_decode(file_get_contents($json['path']), true, flags: JSON_THROW_ON_ERROR));
        $this->assertDatabaseCount('parser_runs', 0);
        $this->assertDatabaseMissing('feature_usage_daily', ['feature' => 'telegram.parser']);
    }

    public function test_duplicate_tick_page_job_and_report_request_do_not_duplicate_work_or_quota(): void
    {
        $project = $this->project();
        $source = $this->source($project);
        $collection = $this->collect($source, [$this->message(), $this->message()]);
        app(MonitoringCollector::class)->collect($collection->id, $this->user->id);
        $key = (string) Str::uuid();
        $one = app(MonitoringManager::class)->requestReport($project->fresh(), 'day', $key);
        $two = app(MonitoringManager::class)->requestReport($project->fresh(), 'day', $key);
        app(MonitoringScheduler::class)->tick();
        app(MonitoringScheduler::class)->tick();
        $this->assertSame($one->id, $two->id);
        $this->assertDatabaseCount('monitoring_materials', 1);
        $this->assertDatabaseCount('monitoring_reports', 1);
        $this->assertSame(1, (int) FeatureUsageDaily::query()->where('feature', 'monitoring.report')->value('used'));
        $this->assertSame(1, (int) FeatureUsageDaily::query()->where('feature', 'monitoring.material')->value('used'));
        $this->assertCount(1, $this->gateway->requests);
    }

    public function test_pause_during_external_response_prevents_late_material_commit(): void
    {
        $project = $this->project();
        $source = $this->source($project);
        $this->gateway->onHistory = fn () => app(MonitoringManager::class)->lifecycle($project->fresh(), 'paused');
        $collection = $this->collect($source, [$this->message()]);
        $this->assertSame('cancelled', $collection->status);
        $this->assertDatabaseCount('monitoring_materials', 0);
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public function test_source_removed_during_resolution_cannot_be_reactivated_by_late_reply(): void
    {
        $project = $this->project();
        $source = app(MonitoringManager::class)->addSource($project, ['platform' => 'telegram', 'input' => 'publicgroup']);
        $this->gateway->onResolve = fn () => app(MonitoringManager::class)->removeSource($source->fresh());
        app(MonitoringCollector::class)->validate($source->id, $this->user->id, $source->generation, $source->fresh('project')->project->generation);
        $this->assertSame('removed', $source->fresh()->status);
        $this->assertNull($source->fresh()->identity);
    }

    public function test_wrong_owner_job_cannot_collect_or_generate(): void
    {
        $project = $this->project();
        $source = $this->source($project);
        app(MonitoringScheduler::class)->tick();
        $collection = $source->collections()->firstOrFail();
        app(MonitoringCollector::class)->collect($collection->id, $this->user->id + 123);
        $this->assertCount(0, $this->gateway->requests);
        $report = app(MonitoringManager::class)->requestReport($project->fresh(), 'day', (string) Str::uuid());
        app(MonitoringReports::class)->build($report->id, $this->user->id + 123);
        $this->assertSame('queued', $report->fresh()->status);
    }

    public function test_worker_crash_expired_lease_and_lost_dispatch_recover_without_http(): void
    {
        $project = $this->project();
        $source = $this->source($project);
        app(MonitoringScheduler::class)->tick();
        $collection = $source->collections()->firstOrFail();
        $collection->update(['status' => 'running', 'lease_token' => (string) Str::uuid(), 'lease_until' => now()->subSecond(), 'dispatched_at' => now()->subMinutes(10)]);
        app(MonitoringScheduler::class)->tick();
        Bus::assertDispatched(CollectMonitoringSource::class, fn ($job) => $job->collectionId === $collection->id);
        $this->gateway->pages = [[$this->message()]];
        app(MonitoringCollector::class)->collect($collection->id, $this->user->id);
        $this->assertSame('completed', $collection->fresh()->status);
        $this->assertDatabaseCount('monitoring_materials', 1);
        Bus::assertDispatched(ValidateMonitoringSource::class);
    }

    public function test_independent_source_failure_preserves_successful_source_and_partial_report(): void
    {
        $project = $this->project();
        $first = $this->source($project);
        $second = $this->source($project->fresh(), 'otherpublic');
        $this->collect($first->fresh('project'), [$this->message()]);
        $this->gateway->failure = new TrackingException('flood_wait', 200);
        $bad = $this->collect($second->fresh('project'));
        $report = $this->report($project);
        $this->assertSame('partial', $report->status);
        $this->assertSame(1, $report->summary['count']);
        $this->assertSame('queued', $bad->status);
        $this->assertTrue($bad->next_attempt_at->gte(now()->addSeconds(200)));
        $this->assertDatabaseCount('monitoring_materials', 1);
    }

    public function test_empty_covered_result_differs_from_missing_history(): void
    {
        $project = $this->project();
        $source = $this->source($project);
        $this->collect($source);
        $report = $this->report($project);
        $this->assertSame('empty', $report->status);
        $this->assertTrue($report->coverage[0]['complete']);
        $this->travel(1)->days();
        $next = $this->report($project);
        $this->assertSame('partial', $next->status);
        $this->assertNotEmpty($next->coverage[0]['gaps']);
    }

    public function test_report_regeneration_creates_idempotent_new_immutable_version(): void
    {
        $project = $this->project();
        $source = $this->source($project);
        $this->collect($source, [$this->message()]);
        $old = $this->report($project);
        $key = (string) Str::uuid();
        $new = app(MonitoringManager::class)->requestReport($project->fresh(), 'day', $key, $old);
        $repeat = app(MonitoringManager::class)->requestReport($project->fresh(), 'day', $key, $old);
        $this->assertSame(2, $new->version);
        $this->assertSame($new->id, $repeat->id);
        $this->assertSame($old->trigger_key, $new->trigger_key);
        $this->assertSame(1, $old->fresh()->version);
    }

    public function test_one_schedule_tick_is_idempotent_and_bounded_catch_up_records_skips(): void
    {
        $project = $this->project();
        $schedule = app(MonitoringManager::class)->saveSchedule($project, ['period' => 'day', 'timezone' => 'UTC', 'time' => '09:00']);
        $schedule->update(['next_run_at' => now()->subDays(4)]);
        app(MonitoringScheduler::class)->tick();
        app(MonitoringScheduler::class)->tick();
        $this->assertDatabaseCount('monitoring_reports', 1);
        $this->assertSame(4, $schedule->fresh()->missed_runs);
        $this->assertTrue($schedule->fresh()->next_run_at->isFuture());
    }

    public function test_expired_report_cleanup_does_not_remove_available_snapshot(): void
    {
        $project = $this->project();
        $source = $this->source($project);
        $this->collect($source, [$this->message()]);
        $old = $this->report($project);
        $new = app(MonitoringManager::class)->requestReport($project->fresh(), 'day', (string) Str::uuid(), $old);
        app(MonitoringReports::class)->build($new->id, $this->user->id);
        $old->update(['expires_at' => now()->subSecond()]);
        $this->artisan('monitoring:maintain --prune')->assertSuccessful();
        $this->assertModelMissing($old);
        $this->assertModelExists($new);
        $this->assertSame(1, $new->items()->count());
    }

    public function test_background_collection_leaves_active_manual_parser_and_its_quota_unchanged(): void
    {
        $project = $this->project();
        $source = $this->source($project);
        $run = ParserRun::query()->create(['run_id' => (string) Str::uuid(), 'user_id' => $this->user->id, 'module' => 'telegram', 'status' => 'running',
            'file_path' => 'manual.json', 'started_at' => now(), 'last_activity_at' => now()]);
        $this->collect($source, [$this->message()]);
        $this->assertSame('running', $run->fresh()->status);
        $this->assertDatabaseCount('parser_runs', 1);
        $this->assertDatabaseMissing('feature_usage_daily', ['feature' => 'telegram.parser']);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
    }
}
