<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Models\NewsMediaReportSchedule;
use App\Models\NewsMediaScheduledReport;
use App\Modules\NewsMediaIntel\Application\Reports\Jobs\GenerateNewsMediaReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class ReportScheduler
{
    public function __construct(
        private ReportConfig $config,
        private ReportPeriod $period,
        private ReportGenerator $generator,
    ) {}

    public function maintain(): int
    {
        $this->config->ensureQueue();
        NewsMediaReportSchedule::query()->where('enabled', true)->where('next_run_at', '<=', now())
            ->select('id')->limit($this->config->integer('dispatch_batch'))->orderBy('next_run_at')->orderBy('id')->get()
            ->each(function (NewsMediaReportSchedule $candidate): void {
                DB::transaction(function () use ($candidate): void {
                    $schedule = NewsMediaReportSchedule::query()->lockForUpdate()->find($candidate->id);
                    if ($schedule === null || ! $schedule->enabled) {
                        return;
                    }
                    if ($schedule->next_run_at->gt(now())) {
                        return;
                    }
                    // Search describes the sample available now. Coalesce missed runs into
                    // the latest due occurrence instead of fabricating historical samples.
                    [$scheduledFor, $nextRun] = $this->period->latestDue($schedule);
                    $this->createOccurrence($schedule, $scheduledFor);
                    $schedule->update(['next_run_at' => $nextRun]);
                });
            });

        $dispatched = $this->dispatch();
        NewsMediaScheduledReport::query()->where('status', NewsMediaScheduledReport::COMPLETED)
            ->whereNull('completion_notified_at')->select('id')->orderBy('id')->limit($this->config->integer('dispatch_batch'))->get()
            ->each(fn ($report) => $this->generator->announce($report->id));

        return $dispatched;
    }

    public function createOccurrence(NewsMediaReportSchedule $schedule, CarbonImmutable $scheduledFor, bool $manual = false): void
    {
        foreach ($schedule->queries as $query) {
            NewsMediaScheduledReport::query()->firstOrCreate([
                'schedule_id' => $schedule->id, 'query' => $query, 'scheduled_for' => $scheduledFor,
            ], ['user_id' => $schedule->user_id, 'status' => NewsMediaScheduledReport::PENDING,
                'brand' => $schedule->brand, 'competitors' => $schedule->competitors,
                'domain' => $schedule->domain, 'search_options' => $schedule->search_options,
                'is_manual' => $manual, 'available_at' => now()]);
        }
    }

    public function dispatch(): int
    {
        $this->config->ensureQueue();
        $reports = NewsMediaScheduledReport::query()->whereIn('status', [NewsMediaScheduledReport::PENDING, NewsMediaScheduledReport::PROCESSING])
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->leaseAvailable()->orderBy('id')->limit($this->config->integer('dispatch_batch'))->get();
        $count = 0;
        foreach ($reports as $report) {
            // Keep queued jobs valid across long backlogs. Only a crashed processing worker is fenced.
            $token = $report->status === NewsMediaScheduledReport::PENDING && $report->lease_token !== null
                ? $report->lease_token : (string) Str::uuid();
            $claimed = NewsMediaScheduledReport::query()->whereKey($report->id)
                ->where('status', $report->status)->where('lease_token', $report->lease_token)
                ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
                ->leaseAvailable()
                ->update(['status' => NewsMediaScheduledReport::PENDING, 'lease_token' => $token,
                    'lease_until' => now()->addSeconds($this->config->integer('lease_seconds'))]);
            if (! $claimed) {
                continue;
            }
            if ($report->attempt_count >= $this->config->integer('max_attempts')) {
                $this->generator->fail($report->id, $token, 'attempts_exhausted');

                continue;
            }
            try {
                Bus::dispatch(new GenerateNewsMediaReport($report->id, $token));
                $count++;
            } catch (Throwable $exception) {
                // A queue outage does not spend a generation attempt or lose the occurrence.
                NewsMediaScheduledReport::query()->whereKey($report->id)->where('lease_token', $token)
                    ->where('status', NewsMediaScheduledReport::PENDING)
                    ->update(['lease_token' => $report->status === NewsMediaScheduledReport::PENDING ? $report->lease_token : null,
                        'lease_until' => null,
                        'available_at' => now()->addSeconds($this->config->integer('retry_seconds'))]);
                Log::warning('News media report queue unavailable.', ['exception_class' => $exception::class]);
            }
        }

        return $count;
    }
}
