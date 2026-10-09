<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Models\FeatureUsageDaily;
use App\Models\TelegramAnalyticsReport;
use App\Models\TelegramAnalyticsSchedule;
use App\Models\User;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\Reports\AnalyticsReportException;
use App\Modules\Telegram\Analytics\Reports\AnalyticsReportGenerator;
use App\Modules\Telegram\Analytics\Reports\AnalyticsReportPeriod;
use App\Modules\Telegram\Analytics\Reports\AnalyticsReportScheduler;
use App\Modules\Telegram\Analytics\Reports\AnalyticsReportScheduleService;
use App\Modules\Telegram\Analytics\Reports\Events\AnalyticsReportCompleted;
use App\Modules\Telegram\Analytics\Reports\Jobs\GenerateAnalyticsReport;
use App\Modules\Telegram\DTO\Result\AnalyticsSummaryResultDTO;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class TelegramAnalyticsReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
        config()->set('telegram_analytics_reports.queue.connection', 'database');
        config()->set('access.plans.free', [...config('access.plans.free'), 'telegram.analytics' => 100]);
        Http::preventStrayRequests();
    }

    public static function intervals(): array
    {
        return [
            'one day' => ['1', '2026-10-10 06:00:00', '2026-10-08 21:00:00', '2026-10-09 20:59:59'],
            'three days' => ['3', '2026-10-12 06:00:00', '2026-10-08 21:00:00', '2026-10-11 20:59:59'],
            'seven days' => ['7', '2026-10-16 06:00:00', '2026-10-08 21:00:00', '2026-10-15 20:59:59'],
            'calendar month' => ['month', '2026-11-01 06:00:00', '2026-09-30 21:00:00', '2026-10-31 20:59:59'],
        ];
    }

    #[DataProvider('intervals')]
    public function test_schedule_uses_selected_frequency_time_and_complete_local_days(string $interval, string $nextRun, string $from, string $to): void
    {
        $user = User::factory()->create();

        $schedule = $this->createSchedule($user, ['interval' => $interval, 'groups' => ['@Example', 'example', 'second_group']]);
        $range = app(AnalyticsReportPeriod::class)->range($schedule, $schedule->next_run_at);

        $this->assertSame(['example', 'second_group'], $schedule->groups);
        $this->assertSame($nextRun, $schedule->next_run_at->format('Y-m-d H:i:s'));
        $this->assertSame($from, $range['date_from']->format('Y-m-d H:i:s'));
        $this->assertSame($to, $range['date_to']->format('Y-m-d H:i:s'));
    }

    public function test_calendar_month_does_not_overflow_and_dst_keeps_the_selected_local_time(): void
    {
        $user = User::factory()->create();
        $this->travelTo(CarbonImmutable::parse('2028-02-29 20:00:00', 'UTC'));
        $month = $this->createSchedule($user, ['interval' => 'month']);
        $range = app(AnalyticsReportPeriod::class)->range($month, $month->next_run_at);
        $this->assertSame('2028-03-01 06:00:00', $month->next_run_at->format('Y-m-d H:i:s'));
        $this->assertSame('2028-02-01', $range['date_from']->setTimezone('Europe/Moscow')->toDateString());
        $this->assertSame('2028-02-29', $range['date_to']->setTimezone('Europe/Moscow')->toDateString());

        $this->travelTo(CarbonImmutable::parse('2026-03-27 20:00:00', 'UTC'));
        $daily = $this->createSchedule($user, ['timezone' => 'Europe/Berlin']);
        $next = app(AnalyticsReportPeriod::class)->nextRun($daily, $daily->next_run_at);
        $this->assertSame('2026-03-29 07:00:00', $next->format('Y-m-d H:i:s'));
        $this->assertSame('09:00', $next->setTimezone('Europe/Berlin')->format('H:i'));
    }

    public function test_scheduler_persists_one_occurrence_per_group_before_advancing_and_never_dispatches_an_active_lease_twice(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user, ['groups' => ['example', 'second_group']]);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));

        $this->assertSame(2, app(AnalyticsReportScheduler::class)->maintain());
        $this->assertSame(0, app(AnalyticsReportScheduler::class)->maintain());

        $this->assertDatabaseCount('telegram_analytics_reports', 2);
        $this->assertSame('2026-10-11 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(GenerateAnalyticsReport::class, 2);
        Bus::assertDispatched(GenerateAnalyticsReport::class, fn ($job) => $job->connection === 'database'
            && $job->queue === 'telegram-analytics-reports' && TelegramAnalyticsReport::find($job->reportId)->lease_token === $job->token);
    }

    public function test_queue_outage_preserves_advanced_occurrence_and_retries_without_spending_an_attempt(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('queue credentials must stay private'));

        $this->assertSame(0, app(AnalyticsReportScheduler::class)->maintain());

        $report = TelegramAnalyticsReport::query()->firstOrFail();
        $this->assertSame('2026-10-11 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, $report->attempt_count);
        $this->assertNull($report->lease_token);
        $this->assertSame(TelegramAnalyticsReport::PENDING, $report->status);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travel(6)->minutes();
        $this->assertSame(1, app(AnalyticsReportScheduler::class)->maintain());
        $this->assertDatabaseCount('telegram_analytics_reports', 1);
        Bus::assertDispatched(GenerateAnalyticsReport::class, fn ($job) => $job->reportId === $report->id);
    }

    public function test_generator_uses_saved_dates_saves_comparison_and_charges_one_credit_across_retry(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = TelegramAnalyticsReport::query()->firstOrFail();
        $data = ['summary' => ['posts' => 12], 'previousReport' => ['summary' => ['posts' => 8]], 'range' => ['from' => '2026-10-09']];
        $mock = $this->mock(TelegramAnalyticsApplicationServiceInterface::class);
        $mock->shouldReceive('buildSummary')->once()->with($user->id,
            Mockery::on(fn ($params) => $params->chatUsername === 'example' && $params->scorePriority === 'balanced'),
            Mockery::on(fn ($from) => $from->format('Y-m-d H:i:sP') === '2026-10-09 00:00:00+03:00'),
            Mockery::on(fn ($to) => $to->format('Y-m-d H:i:sP') === '2026-10-09 23:59:59+03:00'))
            ->andThrow(new RuntimeException('private upstream message'));
        $mock->shouldReceive('buildSummary')->once()->andReturn(new AnalyticsSummaryResultDTO($data));

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);
        $this->assertSame(TelegramAnalyticsReport::PENDING, $report->fresh()->status);
        $this->travel(6)->minutes();
        app(AnalyticsReportScheduler::class)->maintain();
        app(AnalyticsReportGenerator::class)->generate($report->id, $report->fresh()->lease_token);
        app(AnalyticsReportGenerator::class)->generate($report->id, 'obsolete-token');

        $completed = $report->fresh();
        $this->assertSame(TelegramAnalyticsReport::COMPLETED, $completed->status);
        $this->assertSame($data, $completed->data);
        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        $this->assertArrayNotHasKey('data', $completed->toArray());
        $this->assertNull($completed->lease_token);
        Event::assertDispatchedTimes(AnalyticsReportCompleted::class, 1);
        Event::assertDispatched(AnalyticsReportCompleted::class, fn ($event) => $event->reportId === $completed->id);
    }

    public function test_stale_processing_lease_recovers_and_obsolete_worker_cannot_complete_report(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = TelegramAnalyticsReport::query()->firstOrFail();
        $oldToken = $report->lease_token;
        $report->update(['status' => TelegramAnalyticsReport::PROCESSING, 'attempt_count' => 1]);
        $this->mock(TelegramAnalyticsApplicationServiceInterface::class)->shouldNotReceive('buildSummary');
        $this->travel(6)->minutes();

        $this->assertSame(1, app(AnalyticsReportScheduler::class)->maintain());
        app(AnalyticsReportGenerator::class)->generate($report->id, $oldToken);

        $this->assertSame(1, $report->fresh()->attempt_count);
        $this->assertNotSame($oldToken, $report->fresh()->lease_token);
        $this->assertNotSame(TelegramAnalyticsReport::COMPLETED, $report->fresh()->status);
        $this->assertDatabaseCount('telegram_analytics_reports', 1);
        Bus::assertDispatchedTimes(GenerateAnalyticsReport::class, 2);
    }

    public static function unavailableAccounts(): array
    {
        return ['blocked' => ['blocked'], 'unverified' => ['unverified'], 'plan expired' => ['plan']];
    }

    #[DataProvider('unavailableAccounts')]
    public function test_execution_rechecks_account_and_feature_access_without_contacting_telegram(string $reason): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = TelegramAnalyticsReport::query()->firstOrFail();
        match ($reason) {
            'blocked' => $user->update(['is_blocked' => true]),
            'unverified' => $user->forceFill(['email_verified_at' => null])->save(),
            'plan' => config()->set('access.plans.free', [...config('access.plans.free'), 'telegram.analytics' => 0]),
        };
        $this->mock(TelegramAnalyticsApplicationServiceInterface::class)->shouldNotReceive('buildSummary');

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(TelegramAnalyticsReport::FAILED, $report->fresh()->status);
        $this->assertSame(0, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
    }

    public function test_manual_run_can_generate_paused_schedule_and_deleting_schedule_preserves_history(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user);
        app(AnalyticsReportScheduleService::class)->change($user, $schedule->id, 'pause');
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->mock(TelegramAnalyticsApplicationServiceInterface::class)->shouldReceive('buildSummary')->once()
            ->andReturn(new AnalyticsSummaryResultDTO(['marker' => 'durable']));

        app(AnalyticsReportScheduleService::class)->runNow($user, $schedule->id);
        $report = TelegramAnalyticsReport::query()->firstOrFail();
        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);
        app(AnalyticsReportScheduleService::class)->destroy($user, $schedule->id);

        $this->assertSoftDeleted($schedule);
        $this->assertSame(TelegramAnalyticsReport::COMPLETED, $report->fresh()->status);
        $this->assertSame('durable', $report->fresh()->data['marker']);
        $this->assertTrue($report->fresh()->schedule->trashed());
        Bus::assertDispatched(GenerateAnalyticsReport::class, fn ($job) => $job->reportId === $report->id);
        Event::assertDispatched(AnalyticsReportCompleted::class);
    }

    public function test_waiting_in_queue_does_not_consume_attempts_and_original_job_remains_valid(): void
    {
        $user = User::factory()->create();
        $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = TelegramAnalyticsReport::query()->firstOrFail();
        $originalToken = $report->lease_token;

        for ($minute = 0; $minute < 3; $minute++) {
            $this->travel(6)->minutes();
            app(AnalyticsReportScheduler::class)->maintain();
        }

        $this->assertSame(0, $report->fresh()->attempt_count);
        $this->assertSame($originalToken, $report->fresh()->lease_token);
        $this->assertSame(TelegramAnalyticsReport::PENDING, $report->fresh()->status);
        $this->assertDatabaseCount('feature_usage_daily', 0);
        $this->mock(TelegramAnalyticsApplicationServiceInterface::class)->shouldReceive('buildSummary')->once()
            ->andReturn(new AnalyticsSummaryResultDTO(['marker' => 'delayed report']));

        app(AnalyticsReportGenerator::class)->generate($report->id, $originalToken);

        $this->assertSame(TelegramAnalyticsReport::COMPLETED, $report->fresh()->status);
        $this->assertSame(1, $report->fresh()->attempt_count);
        $this->assertSame('delayed report', $report->fresh()->data['marker']);
        Bus::assertDispatchedTimes(GenerateAnalyticsReport::class, 4);
        Event::assertDispatchedTimes(AnalyticsReportCompleted::class, 1);
    }

    public function test_stale_dispatch_snapshot_cannot_override_a_new_retry_backoff(): void
    {
        $user = User::factory()->create();
        $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = TelegramAnalyticsReport::query()->firstOrFail();
        $this->travel(6)->minutes();
        $rescheduled = false;
        TelegramAnalyticsReport::retrieved(function (TelegramAnalyticsReport $candidate) use ($report, &$rescheduled): void {
            if ($candidate->id !== $report->id || $rescheduled) {
                return;
            }
            // A worker completed a failed attempt after the dispatcher read its candidate.
            $rescheduled = true;
            TelegramAnalyticsReport::query()->whereKey($candidate->id)->update([
                'status' => TelegramAnalyticsReport::PENDING, 'lease_token' => null, 'lease_until' => null,
                'available_at' => now()->addMinutes(5),
            ]);
        });

        $this->assertSame(0, app(AnalyticsReportScheduler::class)->dispatch());

        $this->assertTrue($rescheduled);
        $this->assertNull($report->fresh()->lease_token);
        $this->assertTrue($report->fresh()->available_at->isFuture());
        Bus::assertDispatchedTimes(GenerateAnalyticsReport::class, 1);
    }

    public static function incompleteReports(): array
    {
        return [
            'collection cap' => ['telegram_analytics_collection_limit', 'collection_limit'],
            'stalled pagination' => ['telegram_analytics_pagination_stalled', 'pagination_stalled'],
        ];
    }

    #[DataProvider('incompleteReports')]
    public function test_incomplete_report_is_terminal_and_refunds_the_credit(string $code, string $reason): void
    {
        $user = User::factory()->create();
        $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = TelegramAnalyticsReport::query()->firstOrFail();
        $this->mock(TelegramAnalyticsApplicationServiceInterface::class)->shouldReceive('buildSummary')->once()
            ->andThrow(new ExternalServiceRequestException('errors.api.telegram.load_messages_failed', 422, $code));

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(TelegramAnalyticsReport::FAILED, $report->fresh()->status);
        $this->assertSame($reason, $report->fresh()->error_code);
        $this->assertNull($report->fresh()->data);
        $this->assertSame(0, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        Event::assertNotDispatched(AnalyticsReportCompleted::class);
    }

    public function test_sync_queue_configuration_is_rejected_before_any_schedule_is_written(): void
    {
        $user = User::factory()->create();
        config()->set('telegram_analytics_reports.queue.connection', 'sync');
        Bus::fake([GenerateAnalyticsReport::class]);

        try {
            $this->createSchedule($user);
            $this->fail('Sync queue must be rejected.');
        } catch (AnalyticsReportException $exception) {
            $this->assertSame('queue_unavailable', $exception->reason);
        }

        $this->assertDatabaseCount('telegram_analytics_schedules', 0);
        Bus::assertNotDispatched(GenerateAnalyticsReport::class);
    }

    public function test_another_owner_cannot_run_or_change_schedule(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $schedule = $this->createSchedule($owner);
        Bus::fake([GenerateAnalyticsReport::class]);

        foreach (['runNow', 'change'] as $method) {
            try {
                app(AnalyticsReportScheduleService::class)->{$method}($other, $schedule->id, 'pause');
                $this->fail('An owner boundary must be enforced.');
            } catch (ModelNotFoundException) {
                $this->assertTrue($schedule->fresh()->enabled);
            }
        }

        $this->assertDatabaseCount('telegram_analytics_reports', 0);
        Bus::assertNotDispatched(GenerateAnalyticsReport::class);
    }

    public static function ownershipChanges(): array
    {
        return ['before search' => [false], 'during search' => [true]];
    }

    #[DataProvider('ownershipChanges')]
    public function test_report_cannot_be_generated_when_its_schedule_belongs_to_another_owner(bool $duringSearch): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $schedule = $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = TelegramAnalyticsReport::query()->sole();
        $analytics = $this->mock(TelegramAnalyticsApplicationServiceInterface::class);
        if ($duringSearch) {
            $analytics->shouldReceive('buildSummary')->once()->andReturnUsing(function () use ($schedule, $other): AnalyticsSummaryResultDTO {
                $schedule->update(['user_id' => $other->id]);

                return new AnalyticsSummaryResultDTO(['marker' => 'must not be saved']);
            });
        } else {
            $schedule->update(['user_id' => $other->id]);
            $analytics->shouldNotReceive('buildSummary');
        }

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(TelegramAnalyticsReport::FAILED, $report->fresh()->status);
        $this->assertSame('disabled', $report->fresh()->error_code);
        $this->assertNull($report->fresh()->data);
        $this->assertSame(0, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        Event::assertNotDispatched(AnalyticsReportCompleted::class);
    }

    private function createSchedule(User $user, array $overrides = []): TelegramAnalyticsSchedule
    {
        return app(AnalyticsReportScheduleService::class)->create($user, [...[
            'name' => 'Channel report', 'groups' => ['example'], 'interval' => '1', 'send_time' => '09:00',
            'timezone' => 'Europe/Moscow', 'send_to_bot' => false,
        ], ...$overrides]);
    }
}
