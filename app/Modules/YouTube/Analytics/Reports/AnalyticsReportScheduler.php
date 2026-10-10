<?php

namespace App\Modules\YouTube\Analytics\Reports;

use App\Models\YouTubeAnalyticsReport;
use App\Models\YouTubeAnalyticsSchedule;
use App\Modules\YouTube\Analytics\Reports\Jobs\GenerateAnalyticsReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class AnalyticsReportScheduler
{
    public function __construct(
        private AnalyticsReportConfig $config,
        private AnalyticsReportPeriod $period,
        private AnalyticsReportGenerator $generator,
    ) {}

    public function maintain(): int
    {
        $this->config->ensureQueue();
        YouTubeAnalyticsSchedule::query()->where('enabled', true)->where('next_run_at', '<=', now())
            ->select('id')->limit($this->config->integer('dispatch_batch'))->orderBy('next_run_at')->orderBy('id')->get()
            ->each(function (YouTubeAnalyticsSchedule $candidate): void {
                DB::transaction(function () use ($candidate): void {
                    $schedule = YouTubeAnalyticsSchedule::query()->lockForUpdate()->find($candidate->id);
                    if ($schedule === null || ! $schedule->enabled) {
                        return;
                    }
                    $count = 0;
                    while ($schedule->next_run_at->lte(now()) && $count++ < $this->config->integer('catchup_per_schedule')) {
                        $scheduledFor = $schedule->next_run_at;
                        $this->createOccurrence($schedule, $scheduledFor);
                        // Both the immutable occurrence and advancement commit together.
                        $schedule->update(['next_run_at' => $this->period->nextRun($schedule, $scheduledFor)]);
                    }
                });
            });

        $dispatched = $this->dispatch();
        YouTubeAnalyticsReport::query()->where('status', YouTubeAnalyticsReport::COMPLETED)
            ->whereNull('completion_notified_at')->select('id')->orderBy('id')->limit($this->config->integer('dispatch_batch'))->get()
            ->each(fn ($report) => $this->generator->announce($report->id));

        return $dispatched;
    }

    public function createOccurrence(YouTubeAnalyticsSchedule $schedule, CarbonImmutable $scheduledFor, bool $manual = false): void
    {
        $range = $this->period->range($schedule, $scheduledFor);
        foreach ($schedule->channels as $channel) {
            YouTubeAnalyticsReport::query()->firstOrCreate([
                'schedule_id' => $schedule->id, 'channel_input' => $channel, 'scheduled_for' => $scheduledFor,
            ], ['user_id' => $schedule->user_id, ...$range, 'status' => YouTubeAnalyticsReport::PENDING,
                'is_manual' => $manual, 'available_at' => now()]);
        }
    }

    public function dispatch(): int
    {
        $this->config->ensureQueue();
        $reports = YouTubeAnalyticsReport::query()->whereIn('status', [YouTubeAnalyticsReport::PENDING, YouTubeAnalyticsReport::PROCESSING])
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->leaseAvailable()->orderBy('id')->limit($this->config->integer('dispatch_batch'))->get();
        $count = 0;
        foreach ($reports as $report) {
            // Keep queued jobs valid across long backlogs. Only a crashed processing worker is fenced.
            $token = $report->status === YouTubeAnalyticsReport::PENDING && $report->lease_token !== null
                ? $report->lease_token : (string) Str::uuid();
            $claimed = YouTubeAnalyticsReport::query()->whereKey($report->id)
                ->where('status', $report->status)->where('lease_token', $report->lease_token)
                ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
                ->leaseAvailable()
                ->update(['status' => YouTubeAnalyticsReport::PENDING, 'lease_token' => $token,
                    'lease_until' => now()->addSeconds($this->config->integer('lease_seconds'))]);
            if (! $claimed) {
                continue;
            }
            if ($report->attempt_count >= $this->config->integer('max_attempts')) {
                $this->generator->fail($report->id, $token, 'attempts_exhausted');

                continue;
            }
            try {
                Bus::dispatch(new GenerateAnalyticsReport($report->id, $token));
                $count++;
            } catch (Throwable $exception) {
                // A queue outage does not spend a generation attempt or lose the occurrence.
                YouTubeAnalyticsReport::query()->whereKey($report->id)->where('lease_token', $token)
                    ->where('status', YouTubeAnalyticsReport::PENDING)
                    ->update(['lease_token' => $report->status === YouTubeAnalyticsReport::PENDING ? $report->lease_token : null,
                        'lease_until' => null,
                        'available_at' => now()->addSeconds($this->config->integer('retry_seconds'))]);
                Log::warning('YouTube analytics report queue unavailable.', ['exception_class' => $exception::class]);
            }
        }

        return $count;
    }
}
