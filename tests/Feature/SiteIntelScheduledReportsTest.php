<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\SiteIntelReportSchedule;
use App\Models\SiteIntelScheduledReport;
use App\Models\User;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelAnalyticsServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelHostResolverInterface;
use App\Modules\SiteIntel\Application\Reports\Events\SiteIntelReportCompleted;
use App\Modules\SiteIntel\Application\Reports\Jobs\GenerateSiteIntelReport;
use App\Modules\SiteIntel\Application\Reports\ReportException;
use App\Modules\SiteIntel\Application\Reports\ReportGenerator;
use App\Modules\SiteIntel\Application\Reports\ReportPeriod;
use App\Modules\SiteIntel\Application\Reports\ReportScheduler;
use App\Modules\SiteIntel\Application\Reports\ReportScheduleService;
use App\Modules\SiteIntel\DTO\Result\SeoAuditResultDTO;
use App\Modules\SiteIntel\DTO\Result\SiteIntelAnalyticsResultDTO;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class SiteIntelScheduledReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
        config()->set('site_intel_reports.queue.connection', 'site-intel-reports-database');
        config()->set('queue.connections.site-intel-reports-database', [
            ...config('queue.connections.database'), 'retry_after' => 960,
        ]);
        config()->set('access.plans.free', [...config('access.plans.free'),
            'site-intel.analytics' => 100, 'site-intel.seo-audit' => 100]);
        Http::preventStrayRequests();
        $this->instance(SiteIntelHostResolverInterface::class, new class implements SiteIntelHostResolverInterface
        {
            public function resolve(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
    }

    public static function frequencies(): array
    {
        return [
            'one day' => ['1', '2026-10-10 06:00:00'],
            'three days' => ['3', '2026-10-12 06:00:00'],
            'seven days' => ['7', '2026-10-16 06:00:00'],
            'calendar month' => ['month', '2026-11-01 06:00:00'],
        ];
    }

    #[DataProvider('frequencies')]
    public function test_schedule_keeps_frequency_and_time_and_normalizes_duplicate_targets(string $frequency, string $next): void
    {
        $schedule = $this->schedule(User::factory()->create(), [
            'interval' => $frequency, 'targets' => ['Example.org', 'https://example.org/#section', 'https://example.org/Path'],
        ]);

        $this->assertSame($next, $schedule->next_run_at->format('Y-m-d H:i:s'));
        $this->assertSame(['https://example.org/', 'https://example.org/Path'], $schedule->targets);
    }

    public function test_daily_cadence_keeps_local_time_through_dst_and_month_does_not_overflow(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-27 20:00:00', 'UTC'));
        $schedule = $this->schedule(User::factory()->create(), ['timezone' => 'Europe/Berlin']);
        $next = app(ReportPeriod::class)->nextRun($schedule, $schedule->next_run_at);
        $this->assertSame('2026-03-29 07:00:00', $next->format('Y-m-d H:i:s'));
        $this->assertSame('09:00', $next->setTimezone('Europe/Berlin')->format('H:i'));

        $this->travelTo(CarbonImmutable::parse('2028-02-29 20:00:00', 'UTC'));
        $month = $this->schedule(User::factory()->create(), ['interval' => 'month']);
        $this->assertSame('2028-03-01 06:00:00', $month->next_run_at->format('Y-m-d H:i:s'));
    }

    public function test_missed_runs_are_coalesced_into_latest_due_snapshot_per_target_and_cadence_advances(): void
    {
        $schedule = $this->schedule(User::factory()->create(), ['targets' => ['example.org', 'example.com']]);
        Bus::fake([GenerateSiteIntelReport::class]);
        $this->travelTo(CarbonImmutable::parse('2027-10-10 08:00:00', 'UTC'));

        $this->assertSame(2, app(ReportScheduler::class)->maintain());
        $this->assertSame(0, app(ReportScheduler::class)->maintain());

        $this->assertDatabaseCount('site_intel_scheduled_reports', 2);
        $this->assertSame('2027-10-11 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        $this->assertSame('2027-10-10 06:00:00', SiteIntelScheduledReport::query()->firstOrFail()->scheduled_for->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 2);
        Bus::assertDispatched(GenerateSiteIntelReport::class, fn ($job) => $job->connection === 'site-intel-reports-database'
            && $job->queue === 'site-intel-reports' && $job->timeout === 900);
    }

    public function test_coalescing_does_not_run_todays_snapshot_before_selected_local_time(): void
    {
        $schedule = $this->schedule(User::factory()->create());
        Bus::fake([GenerateSiteIntelReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-15 05:59:00', 'UTC'));

        app(ReportScheduler::class)->maintain();

        $this->assertSame('2026-10-14 06:00:00', SiteIntelScheduledReport::query()->firstOrFail()->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-15 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public function test_monthly_missed_snapshots_are_coalesced_to_latest_due_month(): void
    {
        $schedule = $this->schedule(User::factory()->create(), ['interval' => 'month']);
        Bus::fake([GenerateSiteIntelReport::class]);
        $this->travelTo(CarbonImmutable::parse('2028-03-01 05:59:00', 'UTC'));

        app(ReportScheduler::class)->maintain();

        $this->assertSame('2028-02-01 06:00:00', SiteIntelScheduledReport::query()->firstOrFail()->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertSame('2028-03-01 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public static function dstGapFrequencies(): array
    {
        return ['daily' => ['1', '2026-03-30 00:45:00', '2026-03-30 00:30:00', '2026-03-31 00:30:00'],
            'three days' => ['3', '2026-04-01 00:45:00', '2026-04-01 00:30:00', '2026-04-04 00:30:00']];
    }

    #[DataProvider('dstGapFrequencies')]
    public function test_coalescing_restores_selected_time_after_a_dst_gap_in_the_first_due_run(string $interval, string $now, string $expectedDue, string $expectedNext): void
    {
        $schedule = $this->schedule(User::factory()->create(), ['interval' => $interval,
            'timezone' => 'Europe/Berlin', 'send_time' => '02:30']);
        // 02:30 does not exist on March 29 and PHP normalizes that occurrence to 03:30.
        $schedule->update(['next_run_at' => CarbonImmutable::parse('2026-03-29 01:30:00', 'UTC')]);
        Bus::fake([GenerateSiteIntelReport::class]);
        $this->travelTo(CarbonImmutable::parse($now, 'UTC'));

        $this->assertSame(1, app(ReportScheduler::class)->maintain());
        $this->assertSame(0, app(ReportScheduler::class)->maintain());

        $this->assertSame($expectedDue, SiteIntelScheduledReport::query()->firstOrFail()->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertSame($expectedNext, $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        $this->assertTrue($schedule->fresh()->next_run_at->gt(now()));
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public function test_queue_outage_preserves_occurrence_and_advancement_without_spending_generation_attempt(): void
    {
        $schedule = $this->schedule(User::factory()->create());
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('queue credentials'));

        $this->assertSame(0, app(ReportScheduler::class)->maintain());
        $report = SiteIntelScheduledReport::query()->firstOrFail();
        $this->assertSame(0, $report->attempt_count);
        $this->assertNull($report->lease_token);
        $this->assertSame('2026-10-11 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        Bus::fake([GenerateSiteIntelReport::class]);
        $this->travel(6)->minutes();

        $this->assertSame(1, app(ReportScheduler::class)->dispatch());
        $this->assertDatabaseCount('site_intel_scheduled_reports', 1);
        Bus::assertDispatched(GenerateSiteIntelReport::class, fn ($job) => $job->reportId === $report->id);
    }

    public function test_generator_retries_existing_analytics_engine_and_spends_one_credit_once(): void
    {
        $user = User::factory()->create();
        $this->schedule($user);
        Bus::fake([GenerateSiteIntelReport::class]);
        Event::fake([SiteIntelReportCompleted::class]);
        $report = $this->dueReport();
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldReceive('analyze')->once()
            ->with('https://example.org/', 'example.org')->andThrow(new RuntimeException('private upstream error'));

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);
        $this->assertSame(SiteIntelScheduledReport::PENDING, $report->fresh()->status);
        $this->assertSame(1, $this->usage($user, 'site-intel.analytics'));
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldReceive('analyze')->once()
            ->with('https://example.org/', 'example.org')->andReturn(new SiteIntelAnalyticsResultDTO([
                'checkedAt' => '2026-10-10T08:06:00+00:00', 'overview' => ['score' => 88],
            ]));
        $this->travel(6)->minutes();
        app(ReportScheduler::class)->dispatch();
        app(ReportGenerator::class)->generate($report->id, $report->fresh()->lease_token);
        app(ReportGenerator::class)->generate($report->id, 'obsolete');

        $completed = $report->fresh();
        $this->assertSame(SiteIntelScheduledReport::COMPLETED, $completed->status);
        $this->assertSame(2, $completed->attempt_count);
        $this->assertSame(88, $completed->data['overview']['score']);
        $this->assertSame('snapshot_at_check_time', $completed->data['reportSchedule']['metricsBasis']);
        $this->assertSame('2026-10-10T08:06:00+00:00', $completed->data['reportSchedule']['checkedAt']);
        $this->assertSame('2026-10-10T06:00:00+00:00', $completed->data['reportSchedule']['scheduledFor']);
        $this->assertArrayNotHasKey('data', $completed->toArray());
        $this->assertSame(1, $this->usage($user, 'site-intel.analytics'));
        $this->assertSame(0, $this->usage($user, 'site-intel.seo-audit'));
        Event::assertDispatchedTimes(SiteIntelReportCompleted::class, 1);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 2);
    }

    public function test_seo_report_uses_frozen_type_and_options_and_its_own_quota(): void
    {
        $user = User::factory()->create();
        $schedule = $this->schedule($user, ['report_type' => 'seo-audit', 'crawl_limit' => 17, 'platform_type' => 'storefront']);
        Bus::fake([GenerateSiteIntelReport::class]);
        Event::fake([SiteIntelReportCompleted::class]);
        $report = $this->dueReport();
        $schedule->update(['report_type' => 'analytics', 'crawl_limit' => 3, 'platform_type' => 'generic']);
        $this->mock(SeoAuditServiceInterface::class)->shouldReceive('audit')->once()
            ->with('https://example.org/', 17, 'storefront')->andReturn(new SeoAuditResultDTO(['score' => ['value' => 75]]));
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldNotReceive('analyze');

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(SiteIntelScheduledReport::COMPLETED, $report->fresh()->status);
        $this->assertSame('seo-audit', $report->fresh()->data['reportSchedule']['type']);
        $this->assertSame(1, $this->usage($user, 'site-intel.seo-audit'));
        $this->assertSame(0, $this->usage($user, 'site-intel.analytics'));
        Event::assertDispatched(SiteIntelReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public function test_paused_manual_report_generates_without_advancing_cadence_and_history_survives_deletion(): void
    {
        $user = User::factory()->create();
        $schedule = $this->schedule($user);
        $next = $schedule->next_run_at->toIso8601String();
        app(ReportScheduleService::class)->change($user, $schedule->id, 'pause');
        Bus::fake([GenerateSiteIntelReport::class]);
        Event::fake([SiteIntelReportCompleted::class]);
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldReceive('analyze')->once()
            ->andReturn(new SiteIntelAnalyticsResultDTO(['marker' => 'durable']));

        app(ReportScheduleService::class)->runNow($user, $schedule->id);
        app(ReportScheduleService::class)->runNow($user, $schedule->id);
        $report = SiteIntelScheduledReport::query()->firstOrFail();
        app(ReportGenerator::class)->generate($report->id, $report->lease_token);
        app(ReportScheduleService::class)->destroy($user, $schedule->id);

        $this->assertDatabaseCount('site_intel_scheduled_reports', 1);
        $this->assertSoftDeleted($schedule);
        $this->assertTrue($report->fresh()->is_manual);
        $this->assertSame($next, $report->fresh()->schedule->next_run_at->toIso8601String());
        $this->assertSame('durable', $report->fresh()->data['marker']);
        Event::assertDispatched(SiteIntelReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public function test_pausing_automatic_schedule_during_fetch_rejects_result_and_refunds_once(): void
    {
        $user = User::factory()->create();
        $schedule = $this->schedule($user);
        Bus::fake([GenerateSiteIntelReport::class]);
        Event::fake([SiteIntelReportCompleted::class]);
        $report = $this->dueReport();
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldReceive('analyze')->once()
            ->andReturnUsing(function () use ($user, $schedule): SiteIntelAnalyticsResultDTO {
                app(ReportScheduleService::class)->change($user, $schedule->id, 'pause');

                return new SiteIntelAnalyticsResultDTO(['marker' => 'discard']);
            });

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);
        app(ReportGenerator::class)->fail($report->id, $report->lease_token, 'generation_failed');

        $this->assertSame('disabled', $report->fresh()->error_code);
        $this->assertNull($report->fresh()->data);
        $this->assertSame(0, $this->usage($user, 'site-intel.analytics'));
        Event::assertNotDispatched(SiteIntelReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public function test_recovered_processing_lease_fences_old_worker_but_backlogged_pending_job_keeps_token(): void
    {
        $this->schedule(User::factory()->create());
        Bus::fake([GenerateSiteIntelReport::class]);
        $report = $this->dueReport();
        $oldToken = $report->lease_token;
        $this->travel(21)->minutes();
        app(ReportScheduler::class)->dispatch();
        $this->assertSame($oldToken, $report->fresh()->lease_token);
        $report->update(['status' => SiteIntelScheduledReport::PROCESSING, 'attempt_count' => 1]);
        $this->travel(21)->minutes();
        app(ReportScheduler::class)->dispatch();
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldNotReceive('analyze');
        app(ReportGenerator::class)->generate($report->id, $oldToken);

        $this->assertNotSame($oldToken, $report->fresh()->lease_token);
        $this->assertSame(1, $report->fresh()->attempt_count);
        $this->assertNull($report->fresh()->data);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 3);
    }

    public function test_late_worker_result_cannot_overwrite_new_processing_lease(): void
    {
        $this->schedule(User::factory()->create());
        Bus::fake([GenerateSiteIntelReport::class]);
        Event::fake([SiteIntelReportCompleted::class]);
        $report = $this->dueReport();
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldReceive('analyze')->once()
            ->andReturnUsing(function () use ($report): SiteIntelAnalyticsResultDTO {
                $report->update(['lease_token' => 'new-worker-token']);

                return new SiteIntelAnalyticsResultDTO(['marker' => 'stale']);
            });

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertNull($report->fresh()->data);
        $this->assertSame('new-worker-token', $report->fresh()->lease_token);
        Event::assertNotDispatched(SiteIntelReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public static function revokedAccess(): array
    {
        return ['blocked' => ['blocked'], 'unverified' => ['unverified'], 'type access' => ['access'], 'foreign schedule' => ['owner']];
    }

    #[DataProvider('revokedAccess')]
    public function test_generation_rechecks_owner_account_and_selected_report_access_before_external_calls(string $revocation): void
    {
        $user = User::factory()->create();
        $this->schedule($user, ['report_type' => 'seo-audit']);
        Bus::fake([GenerateSiteIntelReport::class]);
        Event::fake([SiteIntelReportCompleted::class]);
        $report = $this->dueReport();
        match ($revocation) {
            'blocked' => $user->update(['is_blocked' => true]),
            'unverified' => $user->forceFill(['email_verified_at' => null])->save(),
            'access' => config()->set('access.plans.free', [...config('access.plans.free'), 'site-intel.seo-audit' => 0]),
            'owner' => $report->update(['schedule_id' => $this->schedule(User::factory()->create())->id]),
        };
        $this->mock(SeoAuditServiceInterface::class)->shouldNotReceive('audit');

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(SiteIntelScheduledReport::FAILED, $report->fresh()->status);
        $this->assertSame(0, $this->usage($user, 'site-intel.seo-audit'));
        Event::assertNotDispatched(SiteIntelReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public function test_exhausted_retries_hide_private_error_and_refund_only_once(): void
    {
        $user = User::factory()->create();
        $this->schedule($user);
        Bus::fake([GenerateSiteIntelReport::class]);
        Event::fake([SiteIntelReportCompleted::class]);
        $report = $this->dueReport();
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldReceive('analyze')->times(3)
            ->andThrow(new RuntimeException('private credentials'));

        for ($attempt = 0; $attempt < 3; $attempt++) {
            app(ReportGenerator::class)->generate($report->id, $report->fresh()->lease_token);
            $this->travel(11)->minutes();
            app(ReportScheduler::class)->dispatch();
        }

        $this->assertSame(SiteIntelScheduledReport::FAILED, $report->fresh()->status);
        $this->assertSame('generation_failed', $report->fresh()->error_code);
        $this->assertSame(3, $report->fresh()->attempt_count);
        $this->assertFalse($report->fresh()->quota_charged);
        $this->assertSame(0, $this->usage($user, 'site-intel.analytics'));
        Event::assertNotDispatched(SiteIntelReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 3);
    }

    public function test_failed_retry_after_midnight_does_not_refund_a_different_days_credit(): void
    {
        $user = User::factory()->create();
        $this->schedule($user);
        Bus::fake([GenerateSiteIntelReport::class]);
        Event::fake([SiteIntelReportCompleted::class]);
        $report = $this->dueReport();
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldReceive('analyze')->once()
            ->andThrow(new RuntimeException('temporarily down'));
        app(ReportGenerator::class)->generate($report->id, $report->lease_token);
        $this->travel(1)->days();
        app(FeatureAccessServiceInterface::class)->consumeResource($user, 'site-intel.analytics');
        $this->instance(SiteIntelHostResolverInterface::class, new class implements SiteIntelHostResolverInterface
        {
            public function resolve(string $host): array
            {
                return ['127.0.0.1'];
            }
        });
        app(ReportScheduler::class)->dispatch();
        app(ReportGenerator::class)->generate($report->id, $report->fresh()->lease_token);

        $this->assertSame('invalid_target', $report->fresh()->error_code);
        $this->assertSame(2, $this->usage($user, 'site-intel.analytics'));
        Event::assertNotDispatched(SiteIntelReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 2);
    }

    public function test_notification_replay_does_not_rerun_engines_or_charge_again(): void
    {
        $user = User::factory()->create();
        $this->schedule($user);
        Bus::fake([GenerateSiteIntelReport::class]);
        $report = $this->dueReport();
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldReceive('analyze')->once()
            ->andReturn(new SiteIntelAnalyticsResultDTO(['marker' => 'persisted']));
        $calls = 0;
        Event::listen(SiteIntelReportCompleted::class, function () use (&$calls): void {
            if (++$calls === 1) {
                throw new RuntimeException('notification down');
            }
        });

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);
        $this->assertNull($report->fresh()->completion_notified_at);
        app(ReportScheduler::class)->maintain();
        app(ReportScheduler::class)->maintain();

        $this->assertSame(2, $calls);
        $this->assertNotNull($report->fresh()->completion_notified_at);
        $this->assertSame('persisted', $report->fresh()->data['marker']);
        $this->assertSame(1, $this->usage($user, 'site-intel.analytics'));
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public static function invalidQueueSettings(): array
    {
        return [
            'sync' => ['site_intel_reports.queue.connection', 'sync'],
            'reservation too short' => ['queue.connections.site-intel-reports-database.retry_after', 900],
            'lease too short' => ['site_intel_reports.lease_seconds', 900],
        ];
    }

    #[DataProvider('invalidQueueSettings')]
    public function test_queue_and_lease_settings_must_safely_cover_job_timeout(string $key, mixed $value): void
    {
        config()->set($key, $value);
        $this->expectExceptionObject(new ReportException('queue_unavailable'));
        $this->schedule(User::factory()->create());
    }

    public function test_foreign_owner_cannot_run_pause_or_delete_schedule(): void
    {
        $schedule = $this->schedule(User::factory()->create());
        $other = User::factory()->create();
        Bus::fake([GenerateSiteIntelReport::class]);
        foreach (['runNow', 'change', 'destroy'] as $action) {
            try {
                app(ReportScheduleService::class)->{$action}($other, $schedule->id, 'pause');
                $this->fail('Owner boundary must be enforced.');
            } catch (ModelNotFoundException) {
                $this->assertTrue($schedule->fresh()->enabled);
            }
        }
        $this->assertDatabaseCount('site_intel_scheduled_reports', 0);
        Bus::assertNotDispatched(GenerateSiteIntelReport::class);
    }

    public function test_owner_can_pause_or_delete_after_losing_type_access_but_cannot_resume(): void
    {
        $user = User::factory()->create();
        $schedule = $this->schedule($user, ['report_type' => 'seo-audit']);
        config()->set('access.plans.free', [...config('access.plans.free'), 'site-intel.seo-audit' => 0]);
        app(ReportScheduleService::class)->change($user, $schedule->id, 'pause');
        try {
            app(ReportScheduleService::class)->change($user, $schedule->id, 'resume');
            $this->fail('Type access must be checked on resume.');
        } catch (ReportException $exception) {
            $this->assertSame('access_denied', $exception->reason);
        }
        app(ReportScheduleService::class)->destroy($user, $schedule->id);

        $this->assertSoftDeleted($schedule);
    }

    public function test_combined_type_limit_can_be_reused_after_soft_delete(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $schedule = $this->schedule($user, ['report_type' => $i % 2 ? 'seo-audit' : 'analytics']);
        }
        try {
            $this->schedule($user);
            $this->fail('Combined schedule limit must be enforced.');
        } catch (ReportException $exception) {
            $this->assertSame('schedule_limit', $exception->reason);
        }
        app(ReportScheduleService::class)->destroy($user, $schedule->id);
        $this->schedule($user);
        $this->assertSame(5, SiteIntelReportSchedule::query()->forUser($user->id)->count());
    }

    private function schedule(User $user, array $overrides = []): SiteIntelReportSchedule
    {
        return app(ReportScheduleService::class)->create($user, [...[
            'name' => 'Site report', 'targets' => ['example.org'], 'report_type' => 'analytics',
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow', 'send_to_bot' => false,
        ], ...$overrides]);
    }

    private function dueReport(): SiteIntelScheduledReport
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(ReportScheduler::class)->maintain();

        return SiteIntelScheduledReport::query()->firstOrFail();
    }

    private function usage(User $user, string $resource): int
    {
        return (int) FeatureUsageDaily::query()->where('user_id', $user->id)->where('feature', $resource)->sum('used');
    }
}
