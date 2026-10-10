<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\NewsMediaReportSchedule;
use App\Models\NewsMediaScheduledReport;
use App\Models\User;
use App\Modules\NewsMediaIntel\Application\Contracts\SearxngSearchClientInterface;
use App\Modules\NewsMediaIntel\Application\Reports\Events\NewsMediaReportCompleted;
use App\Modules\NewsMediaIntel\Application\Reports\Jobs\GenerateNewsMediaReport;
use App\Modules\NewsMediaIntel\Application\Reports\ReportException;
use App\Modules\NewsMediaIntel\Application\Reports\ReportGenerator;
use App\Modules\NewsMediaIntel\Application\Reports\ReportPeriod;
use App\Modules\NewsMediaIntel\Application\Reports\ReportScheduler;
use App\Modules\NewsMediaIntel\Application\Reports\ReportScheduleService;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchResultDTO;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class NewsMediaScheduledReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['news_media_reports.queue.connection' => 'news-media-reports-database']);
        Http::preventStrayRequests();
    }

    public static function frequencies(): array
    {
        return [
            'daily' => ['1', '2026-10-10 06:00:00'],
            'three days' => ['3', '2026-10-12 06:00:00'],
            'weekly' => ['7', '2026-10-16 06:00:00'],
            'month' => ['month', '2026-11-01 06:00:00'],
        ];
    }

    #[DataProvider('frequencies')]
    public function test_frequency_uses_local_delivery_time_and_keeps_search_filter_independent(string $interval, string $next): void
    {
        $this->freezeDate();
        $schedule = $this->schedule(User::factory()->create(), ['interval' => $interval,
            'queries' => [' Brand research ', 'Market news'], 'search_options' => ['timeRange' => 'week']]);

        $this->assertSame($next, $schedule->next_run_at->format('Y-m-d H:i:s'));
        $this->assertSame(['Brand research', 'Market news'], $schedule->queries);
        $this->assertSame('week', $schedule->search_options['timeRange']);
        Http::assertNothingSent();
    }

    public function test_daily_cadence_preserves_time_across_dst_and_monthly_uses_calendar_month(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-27 20:00:00', 'UTC'));
        $schedule = $this->schedule(User::factory()->create(), ['timezone' => 'Europe/Berlin']);
        $next = app(ReportPeriod::class)->nextRun($schedule, $schedule->next_run_at);
        $this->assertSame('2026-03-29 07:00:00', $next->format('Y-m-d H:i:s'));
        $this->travelTo(CarbonImmutable::parse('2028-02-29 20:00:00', 'UTC'));
        $monthly = $this->schedule(User::factory()->create(), ['interval' => 'month']);
        $this->assertSame('2028-03-01 06:00:00', $monthly->next_run_at->format('Y-m-d H:i:s'));
    }

    public function test_missed_runs_coalesce_into_one_latest_occurrence_per_query_and_are_idempotent(): void
    {
        $this->freezeDate();
        $schedule = $this->schedule(User::factory()->create(), ['queries' => ['Brand', 'Market']]);
        Bus::fake([GenerateNewsMediaReport::class]);
        $this->travelTo(CarbonImmutable::parse('2027-10-10 08:00:00', 'UTC'));

        $this->assertSame(2, app(ReportScheduler::class)->maintain());
        $this->assertSame(0, app(ReportScheduler::class)->maintain());
        $this->assertDatabaseCount('news_media_scheduled_reports', 2);
        $this->assertSame('2027-10-11 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        $this->assertSame('2027-10-10 06:00:00', NewsMediaScheduledReport::query()->firstOrFail()->scheduled_for->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 2);
        Bus::assertDispatched(GenerateNewsMediaReport::class, fn ($job) => $job->connection === 'news-media-reports-database'
            && $job->queue === 'news-media-reports' && $job->timeout === 120);
    }

    public function test_missed_run_coalescing_waits_for_selected_local_time(): void
    {
        $this->freezeDate();
        $schedule = $this->schedule(User::factory()->create());
        Bus::fake([GenerateNewsMediaReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-15 05:59:00', 'UTC'));

        app(ReportScheduler::class)->maintain();

        $this->assertSame('2026-10-14 06:00:00', NewsMediaScheduledReport::query()->firstOrFail()->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-15 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 1);
    }

    public function test_queue_outage_keeps_occurrence_and_does_not_spend_attempt(): void
    {
        $this->freezeDate();
        $schedule = $this->schedule(User::factory()->create());
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('private queue credentials'));

        $this->assertSame(0, app(ReportScheduler::class)->maintain());
        $report = NewsMediaScheduledReport::query()->firstOrFail();
        $this->assertSame(0, $report->attempt_count);
        $this->assertNull($report->lease_token);
        $this->assertSame('2026-10-11 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        Bus::fake([GenerateNewsMediaReport::class]);
        $this->travel(6)->minutes();
        $this->assertSame(1, app(ReportScheduler::class)->dispatch());
        $this->assertDatabaseCount('news_media_scheduled_reports', 1);
        Bus::assertDispatched(GenerateNewsMediaReport::class, fn ($job) => $job->reportId === $report->id);
    }

    public function test_transient_failure_retries_saved_report_and_completion_is_idempotent_without_quota(): void
    {
        $this->freezeDate();
        $user = User::factory()->create();
        $this->schedule($user);
        Bus::fake([GenerateNewsMediaReport::class]);
        Event::fake([NewsMediaReportCompleted::class]);
        $report = $this->dueReport();
        $client = $this->client(static function (int $call): NewsSearchResultDTO {
            if ($call === 1) {
                throw new RuntimeException('private upstream detail');
            }

            return new NewsSearchResultDTO([], [], suggestions: ['Fresh idea']);
        });

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);
        $this->assertSame(NewsMediaScheduledReport::PENDING, $report->fresh()->status);
        $this->assertSame(1, $report->fresh()->attempt_count);
        $this->travel(6)->minutes();
        app(ReportScheduler::class)->dispatch();
        app(ReportGenerator::class)->generate($report->id, $report->fresh()->lease_token);
        app(ReportGenerator::class)->generate($report->id, 'obsolete-token');

        $completed = $report->fresh();
        $this->assertSame(NewsMediaScheduledReport::COMPLETED, $completed->status);
        $this->assertSame(2, $completed->attempt_count);
        $this->assertSame(['Fresh idea'], $completed->data['suggestions']);
        $this->assertSame('sample_at_check_time', $completed->data['reportSchedule']['metricsBasis']);
        $this->assertSame('2026-10-10T08:06:00+00:00', $completed->data['reportSchedule']['checkedAt']);
        $this->assertSame('2026-10-10T06:00:00+00:00', $completed->data['reportSchedule']['scheduledFor']);
        $this->assertArrayNotHasKey('data', $completed->toArray());
        $this->assertArrayNotHasKey('lease_token', $completed->toArray());
        $this->assertSame(3, $client->calls);
        $this->assertSame(0, FeatureUsageDaily::query()->where('user_id', $user->id)->count());
        Event::assertDispatchedTimes(NewsMediaReportCompleted::class, 1);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 2);
    }

    public function test_paused_schedule_can_run_manually_without_advancing_cadence_and_history_survives_delete(): void
    {
        $this->freezeDate();
        $user = User::factory()->create();
        $schedule = $this->schedule($user);
        $next = $schedule->next_run_at->toIso8601String();
        app(ReportScheduleService::class)->change($user, $schedule->id, 'pause');
        Bus::fake([GenerateNewsMediaReport::class]);
        Event::fake([NewsMediaReportCompleted::class]);
        $client = $this->client();

        app(ReportScheduleService::class)->runNow($user, $schedule->id);
        app(ReportScheduleService::class)->runNow($user, $schedule->id);
        $report = NewsMediaScheduledReport::query()->firstOrFail();
        app(ReportGenerator::class)->generate($report->id, $report->lease_token);
        app(ReportScheduleService::class)->destroy($user, $schedule->id);

        $this->assertDatabaseCount('news_media_scheduled_reports', 1);
        $this->assertSoftDeleted($schedule);
        $this->assertTrue($report->fresh()->is_manual);
        $this->assertSame($next, $report->fresh()->schedule->next_run_at->toIso8601String());
        $this->assertSame(NewsMediaScheduledReport::COMPLETED, $report->fresh()->status);
        $this->assertSame(2, $client->calls);
        Event::assertDispatched(NewsMediaReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 1);
    }

    public static function revocations(): array
    {
        return ['blocked' => ['blocked'], 'unverified' => ['unverified'], 'paused' => ['paused'],
            'deleted' => ['deleted'], 'foreign schedule' => ['owner']];
    }

    #[DataProvider('revocations')]
    public function test_generation_revalidates_owner_account_and_schedule_before_external_search(string $change): void
    {
        $this->freezeDate();
        $user = User::factory()->create();
        $schedule = $this->schedule($user);
        Bus::fake([GenerateNewsMediaReport::class]);
        Event::fake([NewsMediaReportCompleted::class]);
        $report = $this->dueReport();
        $this->revoke($change, $user, $schedule, $report);
        $client = $this->client();

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(NewsMediaScheduledReport::FAILED, $report->fresh()->status);
        $this->assertNull($report->fresh()->data);
        $this->assertSame(0, $client->calls);
        Event::assertNotDispatched(NewsMediaReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 1);
    }

    #[DataProvider('revocations')]
    public function test_generation_revalidates_changes_while_external_search_is_in_flight(string $change): void
    {
        $this->freezeDate();
        $user = User::factory()->create();
        $schedule = $this->schedule($user);
        Bus::fake([GenerateNewsMediaReport::class]);
        Event::fake([NewsMediaReportCompleted::class]);
        $report = $this->dueReport();
        $client = $this->client(function (int $call) use ($change, $user, $schedule, $report): NewsSearchResultDTO {
            if ($call === 1) {
                $this->revoke($change, $user, $schedule, $report);
            }

            return new NewsSearchResultDTO([], []);
        });

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(NewsMediaScheduledReport::FAILED, $report->fresh()->status);
        $this->assertNull($report->fresh()->data);
        $this->assertSame(2, $client->calls);
        Event::assertNotDispatched(NewsMediaReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 1);
    }

    public function test_processing_lease_recovery_fences_old_worker_and_pending_backlog_preserves_token(): void
    {
        $this->freezeDate();
        $this->schedule(User::factory()->create());
        Bus::fake([GenerateNewsMediaReport::class]);
        $report = $this->dueReport();
        $oldToken = $report->lease_token;
        $this->travel(6)->minutes();
        app(ReportScheduler::class)->dispatch();
        $this->assertSame($oldToken, $report->fresh()->lease_token);
        $report->update(['status' => NewsMediaScheduledReport::PROCESSING, 'attempt_count' => 1]);
        $this->travel(6)->minutes();
        app(ReportScheduler::class)->dispatch();
        $client = $this->client();
        app(ReportGenerator::class)->generate($report->id, $oldToken);

        $this->assertNotSame($oldToken, $report->fresh()->lease_token);
        $this->assertSame(1, $report->fresh()->attempt_count);
        $this->assertSame(0, $client->calls);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 3);
    }

    public function test_late_search_result_cannot_overwrite_replacement_worker_lease(): void
    {
        $this->freezeDate();
        $this->schedule(User::factory()->create());
        Bus::fake([GenerateNewsMediaReport::class]);
        Event::fake([NewsMediaReportCompleted::class]);
        $report = $this->dueReport();
        $this->client(static function () use ($report): NewsSearchResultDTO {
            $report->update(['lease_token' => 'replacement-worker']);

            return new NewsSearchResultDTO([], []);
        });

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertNull($report->fresh()->data);
        $this->assertSame('replacement-worker', $report->fresh()->lease_token);
        Event::assertNotDispatched(NewsMediaReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 1);
    }

    public function test_attempt_budget_exhaustion_stores_only_public_error_code(): void
    {
        $this->freezeDate();
        $this->schedule(User::factory()->create());
        Bus::fake([GenerateNewsMediaReport::class]);
        Event::fake([NewsMediaReportCompleted::class]);
        $report = $this->dueReport();
        $client = $this->client(static fn () => throw new RuntimeException('private credentials'));
        for ($attempt = 0; $attempt < 3; $attempt++) {
            app(ReportGenerator::class)->generate($report->id, $report->fresh()->lease_token);
            $this->travel(11)->minutes();
            app(ReportScheduler::class)->dispatch();
        }

        $this->assertSame(NewsMediaScheduledReport::FAILED, $report->fresh()->status);
        $this->assertSame('generation_failed', $report->fresh()->error_code);
        $this->assertSame(3, $report->fresh()->attempt_count);
        $this->assertSame(3, $client->calls);
        $this->assertNull($report->fresh()->data);
        Event::assertNotDispatched(NewsMediaReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 3);
    }

    public function test_notification_retry_preserves_completed_report_and_does_not_repeat_search(): void
    {
        $this->freezeDate();
        $this->schedule(User::factory()->create());
        Bus::fake([GenerateNewsMediaReport::class]);
        $report = $this->dueReport();
        $client = $this->client();
        $notifications = 0;
        Event::listen(NewsMediaReportCompleted::class, function () use (&$notifications): void {
            if (++$notifications === 1) {
                throw new RuntimeException('notification unavailable');
            }
        });

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);
        $this->assertNull($report->fresh()->completion_notified_at);
        app(ReportScheduler::class)->maintain();
        app(ReportScheduler::class)->maintain();

        $this->assertSame(2, $notifications);
        $this->assertNotNull($report->fresh()->completion_notified_at);
        $this->assertSame(NewsMediaScheduledReport::COMPLETED, $report->fresh()->status);
        $this->assertSame(2, $client->calls);
        Bus::assertDispatchedTimes(GenerateNewsMediaReport::class, 1);
    }

    public function test_schedule_owner_boundary_applies_to_run_pause_resume_and_delete(): void
    {
        $this->freezeDate();
        $schedule = $this->schedule(User::factory()->create());
        $other = User::factory()->create();
        Bus::fake([GenerateNewsMediaReport::class]);
        foreach (['runNow', 'change', 'destroy'] as $action) {
            try {
                app(ReportScheduleService::class)->{$action}($other, $schedule->id, 'pause');
                $this->fail('The owner boundary must be enforced.');
            } catch (ModelNotFoundException) {
                $this->assertTrue($schedule->fresh()->enabled);
            }
        }
        $this->assertDatabaseCount('news_media_scheduled_reports', 0);
        Bus::assertNotDispatched(GenerateNewsMediaReport::class);
    }

    public function test_schedule_limit_is_reusable_after_delete_and_resume_recomputes_next_run(): void
    {
        $this->freezeDate();
        $user = User::factory()->create();
        for ($index = 0; $index < 5; $index++) {
            $schedule = $this->schedule($user);
        }
        try {
            $this->schedule($user);
            $this->fail('The schedule limit must be enforced.');
        } catch (ReportException $exception) {
            $this->assertSame('schedule_limit', $exception->reason);
        }
        app(ReportScheduleService::class)->change($user, $schedule->id, 'pause');
        $this->travel(5)->days();
        app(ReportScheduleService::class)->change($user, $schedule->id, 'resume');
        $this->assertSame('2026-10-15 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        app(ReportScheduleService::class)->destroy($user, $schedule->id);
        $this->schedule($user);
        $this->assertSame(5, NewsMediaReportSchedule::query()->forUser($user->id)->count());
    }

    public static function invalidQueueSettings(): array
    {
        return ['sync' => ['news_media_reports.queue.connection', 'sync'],
            'reservation' => ['queue.connections.news-media-reports-database.retry_after', 120],
            'lease' => ['news_media_reports.lease_seconds', 120]];
    }

    #[DataProvider('invalidQueueSettings')]
    public function test_durable_queue_settings_must_cover_worker_timeout(string $key, mixed $value): void
    {
        config()->set($key, $value);
        $this->expectExceptionObject(new ReportException('queue_unavailable'));
        $this->schedule(User::factory()->create());
    }

    private function revoke(string $change, User $user, NewsMediaReportSchedule $schedule, NewsMediaScheduledReport $report): void
    {
        match ($change) {
            'blocked' => $user->update(['is_blocked' => true]),
            'unverified' => $user->forceFill(['email_verified_at' => null])->save(),
            'paused' => $schedule->update(['enabled' => false]),
            'deleted' => $schedule->delete(),
            'owner' => $report->update(['schedule_id' => $this->schedule(User::factory()->create())->id]),
        };
    }

    private function freezeDate(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
    }

    private function schedule(User $user, array $overrides = []): NewsMediaReportSchedule
    {
        return app(ReportScheduleService::class)->create($user, [...[
            'name' => 'News report', 'queries' => ['Brand research'], 'brand' => 'Brand',
            'competitors' => ['Rival'], 'domain' => 'example.org',
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow', 'send_to_bot' => false,
        ], ...$overrides]);
    }

    private function dueReport(): NewsMediaScheduledReport
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(ReportScheduler::class)->maintain();

        return NewsMediaScheduledReport::query()->firstOrFail();
    }

    private function client(?Closure $behavior = null): object
    {
        $client = new class($behavior) implements SearxngSearchClientInterface
        {
            public int $calls = 0;

            public function __construct(private readonly ?Closure $behavior) {}

            public function search(string $query, ?NewsSearchOptionsDTO $options = null, ?float $deadline = null): NewsSearchResultDTO
            {
                $this->calls++;

                return $this->behavior === null ? new NewsSearchResultDTO([], []) : ($this->behavior)($this->calls, $query, $options);
            }
        };
        $this->instance(SearxngSearchClientInterface::class, $client);

        return $client;
    }
}
