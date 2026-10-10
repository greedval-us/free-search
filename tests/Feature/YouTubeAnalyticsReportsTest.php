<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Models\YouTubeAnalyticsReport;
use App\Models\YouTubeAnalyticsSchedule;
use App\Modules\YouTube\Analytics\Reports\AnalyticsReportException;
use App\Modules\YouTube\Analytics\Reports\AnalyticsReportGenerator;
use App\Modules\YouTube\Analytics\Reports\AnalyticsReportPeriod;
use App\Modules\YouTube\Analytics\Reports\AnalyticsReportScheduler;
use App\Modules\YouTube\Analytics\Reports\AnalyticsReportScheduleService;
use App\Modules\YouTube\Analytics\Reports\Events\AnalyticsReportCompleted;
use App\Modules\YouTube\Analytics\Reports\Jobs\GenerateAnalyticsReport;
use App\Modules\YouTube\Analytics\Reports\PublicYouTubeChannel;
use App\Modules\YouTube\Analytics\Reports\ScheduledYouTubeAnalytics;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
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

class YouTubeAnalyticsReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
        config()->set('youtube_analytics_reports.queue.connection', 'database');
        config()->set('access.plans.free', [...config('access.plans.free'), 'youtube.analytics' => 100]);
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

        $schedule = $this->createSchedule($user, ['interval' => $interval, 'channels' => ['@Example', 'example', 'second_channel']]);
        $range = app(AnalyticsReportPeriod::class)->range($schedule, $schedule->next_run_at);

        $this->assertSame(['@example', '@second_channel'], $schedule->channels);
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

    public function test_scheduler_persists_one_occurrence_per_channel_before_advancing_and_never_dispatches_an_active_lease_twice(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user, ['channels' => ['@example', '@second_channel']]);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));

        $this->assertSame(2, app(AnalyticsReportScheduler::class)->maintain());
        $this->assertSame(0, app(AnalyticsReportScheduler::class)->maintain());

        $this->assertDatabaseCount('youtube_analytics_reports', 2);
        $this->assertSame('2026-10-11 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(GenerateAnalyticsReport::class, 2);
        Bus::assertDispatched(GenerateAnalyticsReport::class, fn ($job) => $job->connection === 'database'
            && $job->queue === 'youtube-analytics-reports' && YouTubeAnalyticsReport::find($job->reportId)->lease_token === $job->token);
    }

    public function test_queue_outage_preserves_advanced_occurrence_and_retries_without_spending_an_attempt(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('queue credentials must stay private'));

        $this->assertSame(0, app(AnalyticsReportScheduler::class)->maintain());

        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $this->assertSame('2026-10-11 06:00:00', $schedule->fresh()->next_run_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, $report->attempt_count);
        $this->assertNull($report->lease_token);
        $this->assertSame(YouTubeAnalyticsReport::PENDING, $report->status);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travel(6)->minutes();
        $this->assertSame(1, app(AnalyticsReportScheduler::class)->maintain());
        $this->assertDatabaseCount('youtube_analytics_reports', 1);
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
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $data = ['summary' => ['posts' => 12], 'previousReport' => ['summary' => ['posts' => 8]], 'range' => ['from' => '2026-10-09']];
        $mock = $this->mock(ScheduledYouTubeAnalytics::class);
        $mock->shouldReceive('build')->once()->with('@example',
            Mockery::on(fn ($from) => $from instanceof CarbonImmutable && $from->format('Y-m-d H:i:sP') === '2026-10-08 21:00:00+00:00'),
            Mockery::on(fn ($to) => $to instanceof CarbonImmutable && $to->format('Y-m-d H:i:sP') === '2026-10-09 20:59:59+00:00'),
            'Europe/Moscow')
            ->andThrow(new RuntimeException('private upstream message'));
        $mock->shouldReceive('build')->once()->andReturn($data);

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);
        $this->assertSame(YouTubeAnalyticsReport::PENDING, $report->fresh()->status);
        $this->travel(6)->minutes();
        app(AnalyticsReportScheduler::class)->maintain();
        app(AnalyticsReportGenerator::class)->generate($report->id, $report->fresh()->lease_token);
        app(AnalyticsReportGenerator::class)->generate($report->id, 'obsolete-token');

        $completed = $report->fresh();
        $this->assertSame(YouTubeAnalyticsReport::COMPLETED, $completed->status);
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
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $oldToken = $report->lease_token;
        $report->update(['status' => YouTubeAnalyticsReport::PROCESSING, 'attempt_count' => 1]);
        $this->mock(ScheduledYouTubeAnalytics::class)->shouldNotReceive('build');
        $this->travel(6)->minutes();

        $this->assertSame(1, app(AnalyticsReportScheduler::class)->maintain());
        app(AnalyticsReportGenerator::class)->generate($report->id, $oldToken);

        $this->assertSame(1, $report->fresh()->attempt_count);
        $this->assertNotSame($oldToken, $report->fresh()->lease_token);
        $this->assertNotSame(YouTubeAnalyticsReport::COMPLETED, $report->fresh()->status);
        $this->assertDatabaseCount('youtube_analytics_reports', 1);
        Bus::assertDispatchedTimes(GenerateAnalyticsReport::class, 2);
    }

    public static function unavailableAccounts(): array
    {
        return ['blocked' => ['blocked'], 'unverified' => ['unverified'], 'plan expired' => ['plan']];
    }

    #[DataProvider('unavailableAccounts')]
    public function test_execution_rechecks_account_and_feature_access_without_contacting_youtube(string $reason): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        match ($reason) {
            'blocked' => $user->update(['is_blocked' => true]),
            'unverified' => $user->forceFill(['email_verified_at' => null])->save(),
            'plan' => config()->set('access.plans.free', [...config('access.plans.free'), 'youtube.analytics' => 0]),
        };
        $this->mock(ScheduledYouTubeAnalytics::class)->shouldNotReceive('build');

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(YouTubeAnalyticsReport::FAILED, $report->fresh()->status);
        $this->assertSame(0, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
    }

    public function test_manual_run_can_generate_paused_schedule_and_deleting_schedule_preserves_history(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user);
        app(AnalyticsReportScheduleService::class)->change($user, $schedule->id, 'pause');
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->mock(ScheduledYouTubeAnalytics::class)->shouldReceive('build')->once()
            ->andReturn(['marker' => 'durable']);

        app(AnalyticsReportScheduleService::class)->runNow($user, $schedule->id);
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);
        app(AnalyticsReportScheduleService::class)->destroy($user, $schedule->id);

        $this->assertSoftDeleted($schedule);
        $this->assertSame(YouTubeAnalyticsReport::COMPLETED, $report->fresh()->status);
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
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $originalToken = $report->lease_token;

        for ($minute = 0; $minute < 3; $minute++) {
            $this->travel(6)->minutes();
            app(AnalyticsReportScheduler::class)->maintain();
        }

        $this->assertSame(0, $report->fresh()->attempt_count);
        $this->assertSame($originalToken, $report->fresh()->lease_token);
        $this->assertSame(YouTubeAnalyticsReport::PENDING, $report->fresh()->status);
        $this->assertDatabaseCount('feature_usage_daily', 0);
        $this->mock(ScheduledYouTubeAnalytics::class)->shouldReceive('build')->once()
            ->andReturn(['marker' => 'delayed report']);

        app(AnalyticsReportGenerator::class)->generate($report->id, $originalToken);

        $this->assertSame(YouTubeAnalyticsReport::COMPLETED, $report->fresh()->status);
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
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $this->travel(6)->minutes();
        $rescheduled = false;
        YouTubeAnalyticsReport::retrieved(function (YouTubeAnalyticsReport $candidate) use ($report, &$rescheduled): void {
            if ($candidate->id !== $report->id || $rescheduled) {
                return;
            }
            // A worker completed a failed attempt after the dispatcher read its candidate.
            $rescheduled = true;
            YouTubeAnalyticsReport::query()->whereKey($candidate->id)->update([
                'status' => YouTubeAnalyticsReport::PENDING, 'lease_token' => null, 'lease_until' => null,
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
            'collection cap' => ['youtube_analytics_collection_limit', 'collection_limit'],
            'stalled pagination' => ['youtube_analytics_pagination_stalled', 'pagination_stalled'],
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
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $this->mock(ScheduledYouTubeAnalytics::class)->shouldReceive('build')->once()
            ->andThrow(new ExternalServiceRequestException('errors.api.youtube.load_messages_failed', 422, $code));

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(YouTubeAnalyticsReport::FAILED, $report->fresh()->status);
        $this->assertSame($reason, $report->fresh()->error_code);
        $this->assertNull($report->fresh()->data);
        $this->assertSame(0, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        Event::assertNotDispatched(AnalyticsReportCompleted::class);
    }

    public function test_sync_queue_configuration_is_rejected_before_any_schedule_is_written(): void
    {
        $user = User::factory()->create();
        config()->set('youtube_analytics_reports.queue.connection', 'sync');
        Bus::fake([GenerateAnalyticsReport::class]);

        try {
            $this->createSchedule($user);
            $this->fail('Sync queue must be rejected.');
        } catch (AnalyticsReportException $exception) {
            $this->assertSame('queue_unavailable', $exception->reason);
        }

        $this->assertDatabaseCount('youtube_analytics_schedules', 0);
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

        $this->assertDatabaseCount('youtube_analytics_reports', 0);
        Bus::assertNotDispatched(GenerateAnalyticsReport::class);
    }

    public static function publicChannels(): array
    {
        return [
            'plain handle' => ['Example', '@example'],
            'handle' => ['@Example', '@example'],
            'unicode handle' => ['@Канал', '@канал'],
            'ID exact case' => ['UCaBcDeFgHiJkLmNoPqRsTuv', 'UCaBcDeFgHiJkLmNoPqRsTuv'],
            'handle URL' => ['https://www.youtube.com/@Example/', '@example'],
            'mobile URL' => ['http://m.youtube.com/@Example', '@example'],
            'URL without scheme' => ['youtube.com/@Example', '@example'],
            'ID URL' => ['https://youtube.com/channel/UCaBcDeFgHiJkLmNoPqRsTuv', 'UCaBcDeFgHiJkLmNoPqRsTuv'],
            'video URL' => ['https://youtube.com/watch?v=abcdefghijk', null],
            'short video URL' => ['https://youtu.be/abcdefghijk', null],
            'extra path' => ['https://youtube.com/@Example/videos', null],
            'host suffix' => ['https://youtube.com.attacker.test/@Example', null],
            'credentials' => ['https://user:pass@youtube.com/@Example', null],
            'port' => ['https://youtube.com:8443/@Example', null],
            'query' => ['https://youtube.com/@Example?x=1', null],
            'fragment' => ['https://youtube.com/@Example#videos', null],
            'other scheme' => ['ftp://youtube.com/@Example', null],
            'space' => ['hello world', null],
            'embedded newline' => ["@hello\nworld", null],
            'path' => ['../Example', null],
            'too short' => ['@ab', null],
            'too long' => ['@'.str_repeat('a', 31), null],
            'array' => [[], null],
            'number' => [123, null],
        ];
    }

    #[DataProvider('publicChannels')]
    public function test_channel_normalization_accepts_only_public_channel_inputs(mixed $input, ?string $normalized): void
    {
        $this->assertSame($normalized, PublicYouTubeChannel::normalize($input));
    }

    public function test_channels_are_deduplicated_and_invalid_channels_cannot_be_scheduled(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user, ['channels' => [
            '@Example', 'example', 'youtube.com/@EXAMPLE',
            'UCaBcDeFgHiJkLmNoPqRsTuv', 'UCaBcDeFgHiJkLmNoPqRsTuv', 'UCAbCdEfGhIjKlMnOpQrStUv',
        ]]);

        $this->assertSame(['@example', 'UCaBcDeFgHiJkLmNoPqRsTuv', 'UCAbCdEfGhIjKlMnOpQrStUv'], $schedule->channels);

        try {
            $this->createSchedule($user, ['channels' => ['https://youtube.com/watch?v=abcdefghijk']]);
            $this->fail('A video URL cannot be scheduled as a channel.');
        } catch (AnalyticsReportException $exception) {
            $this->assertSame('invalid_channels', $exception->reason);
        }

        $this->assertDatabaseCount('youtube_analytics_schedules', 1);
    }

    public function test_occurrences_preserve_distinct_case_sensitive_ids_and_unicode_handles(): void
    {
        $user = User::factory()->create();
        $this->createSchedule($user, ['channels' => ['UCaBcDeFgHiJkLmNoPqRsTuv', 'UCAbCdEfGhIjKlMnOpQrStUv', '@café']]);
        $this->createSchedule($user, ['channels' => ['@cafe', '@café']]);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));

        $this->assertSame(5, app(AnalyticsReportScheduler::class)->maintain());
        $this->assertSame(0, app(AnalyticsReportScheduler::class)->maintain());

        $this->assertSame([
            'UCaBcDeFgHiJkLmNoPqRsTuv', 'UCAbCdEfGhIjKlMnOpQrStUv', '@café', '@cafe', '@café',
        ], YouTubeAnalyticsReport::query()->orderBy('id')->pluck('channel_input')->all());
        Bus::assertDispatchedTimes(GenerateAnalyticsReport::class, 5);
    }

    public function test_schedule_limit_can_be_reused_after_soft_deletion(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $schedule = $this->createSchedule($user);
        }

        try {
            $this->createSchedule($user);
            $this->fail('Schedule limit must be enforced.');
        } catch (AnalyticsReportException $exception) {
            $this->assertSame('schedule_limit', $exception->reason);
        }
        app(AnalyticsReportScheduleService::class)->destroy($user, $schedule->id);
        $replacement = $this->createSchedule($user);

        $this->assertModelExists($replacement);
        $this->assertSame(5, YouTubeAnalyticsSchedule::query()->forUser($user->id)->count());
    }

    public function test_exhausted_generation_retries_refund_once_and_preserve_safe_failure(): void
    {
        $user = User::factory()->create();
        $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $this->mock(ScheduledYouTubeAnalytics::class)->shouldReceive('build')->times(3)
            ->andThrow(new RuntimeException('private API credentials'));

        for ($attempt = 0; $attempt < 3; $attempt++) {
            app(AnalyticsReportGenerator::class)->generate($report->id, $report->fresh()->lease_token);
            $this->travel(11)->minutes();
            app(AnalyticsReportScheduler::class)->dispatch();
        }
        app(AnalyticsReportGenerator::class)->fail($report->id, 'old-token', 'generation_failed');

        $this->assertSame(YouTubeAnalyticsReport::FAILED, $report->fresh()->status);
        $this->assertSame('generation_failed', $report->fresh()->error_code);
        $this->assertSame(3, $report->fresh()->attempt_count);
        $this->assertNull($report->fresh()->data);
        $this->assertFalse($report->fresh()->quota_charged);
        $this->assertSame(0, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        Event::assertNotDispatched(AnalyticsReportCompleted::class);
    }

    public function test_terminal_retry_after_midnight_does_not_refund_another_days_usage(): void
    {
        $user = User::factory()->create();
        $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $mock = $this->mock(ScheduledYouTubeAnalytics::class);
        $mock->shouldReceive('build')->once()->andThrow(new RuntimeException('temporarily unavailable'));
        $mock->shouldReceive('build')->once()->andThrow(new ExternalServiceRequestException(
            'errors.api.youtube.load_messages_failed', 422, 'youtube_analytics_collection_limit'));
        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);
        $this->travel(1)->days();
        $this->assertTrue(app(FeatureAccessServiceInterface::class)->consumeResource($user, 'youtube.analytics')->allowed);
        app(AnalyticsReportScheduler::class)->dispatch();

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->fresh()->lease_token);

        $this->assertSame(YouTubeAnalyticsReport::FAILED, $report->fresh()->status);
        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $user->id)->whereDate('usage_date', '2026-10-10')->sum('used'));
        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $user->id)->whereDate('usage_date', '2026-10-11')->sum('used'));
        Event::assertNotDispatched(AnalyticsReportCompleted::class);
    }

    public function test_notification_failure_replays_completion_without_generating_or_charging_again(): void
    {
        $user = User::factory()->create();
        $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $this->mock(ScheduledYouTubeAnalytics::class)->shouldReceive('build')->once()->andReturn(['marker' => 'saved']);
        $notifications = 0;
        Event::listen(AnalyticsReportCompleted::class, function () use (&$notifications): void {
            if (++$notifications === 1) {
                throw new RuntimeException('temporary notification failure');
            }
        });

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);
        $this->assertSame(YouTubeAnalyticsReport::COMPLETED, $report->fresh()->status);
        $this->assertNull($report->fresh()->completion_notified_at);
        app(AnalyticsReportScheduler::class)->maintain();
        app(AnalyticsReportScheduler::class)->maintain();

        $this->assertSame(2, $notifications);
        $this->assertNotNull($report->fresh()->completion_notified_at);
        $this->assertSame(['marker' => 'saved'], $report->fresh()->data);
        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        Bus::assertDispatchedTimes(GenerateAnalyticsReport::class, 1);
    }

    public function test_schedule_revoked_during_generation_prevents_snapshot_and_refunds_credit(): void
    {
        $user = User::factory()->create();
        $schedule = $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = YouTubeAnalyticsReport::query()->firstOrFail();
        $this->mock(ScheduledYouTubeAnalytics::class)->shouldReceive('build')->once()->andReturnUsing(function () use ($user, $schedule): array {
            app(AnalyticsReportScheduleService::class)->change($user, $schedule->id, 'pause');

            return ['marker' => 'must not persist'];
        });

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(YouTubeAnalyticsReport::FAILED, $report->fresh()->status);
        $this->assertSame('disabled', $report->fresh()->error_code);
        $this->assertNull($report->fresh()->data);
        $this->assertSame(0, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        Event::assertNotDispatched(AnalyticsReportCompleted::class);
    }

    public function test_foreign_schedule_cannot_be_used_for_generation(): void
    {
        $user = User::factory()->create();
        $foreign = $this->createSchedule(User::factory()->create(), ['channels' => ['@foreign']]);
        $this->createSchedule($user);
        Bus::fake([GenerateAnalyticsReport::class]);
        Event::fake([AnalyticsReportCompleted::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00', 'UTC'));
        app(AnalyticsReportScheduler::class)->maintain();
        $report = YouTubeAnalyticsReport::query()->forUser($user->id)->firstOrFail();
        $report->update(['schedule_id' => $foreign->id]);
        $this->mock(ScheduledYouTubeAnalytics::class)->shouldNotReceive('build');

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(YouTubeAnalyticsReport::FAILED, $report->fresh()->status);
        $this->assertSame('disabled', $report->fresh()->error_code);
        $this->assertSame(0, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        Event::assertNotDispatched(AnalyticsReportCompleted::class);
    }

    private function createSchedule(User $user, array $overrides = []): YouTubeAnalyticsSchedule
    {
        return app(AnalyticsReportScheduleService::class)->create($user, [...[
            'name' => 'Channel report', 'channels' => ['@example'], 'interval' => '1', 'send_time' => '09:00',
            'timezone' => 'Europe/Moscow', 'send_to_bot' => false,
        ], ...$overrides]);
    }
}
