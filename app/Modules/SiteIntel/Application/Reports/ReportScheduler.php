<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Models\SiteIntelReportSchedule;
use App\Models\SiteIntelScheduledReport;
use App\Modules\SiteIntel\Application\Reports\Jobs\GenerateSiteIntelReport;
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
        SiteIntelReportSchedule::query()->where('enabled', true)->where('next_run_at', '<=', now())
            ->select('id')->limit($this->config->integer('dispatch_batch'))->orderBy('next_run_at')->orderBy('id')->get()
            ->each(function (SiteIntelReportSchedule $candidate): void {
                DB::transaction(function () use ($candidate): void {
                    $schedule = SiteIntelReportSchedule::query()->lockForUpdate()->find($candidate->id);
                    if ($schedule === null || ! $schedule->enabled) {
                        return;
                    }
                    if ($schedule->next_run_at->gt(now())) {
                        return;
                    }
                    // A site check describes its current state. Coalesce missed checks into
                    // the latest due occurrence instead of fabricating past snapshots.
                    [$scheduledFor, $nextRun] = $this->period->latestDue($schedule);
                    $this->createOccurrence($schedule, $scheduledFor);
                    $schedule->update(['next_run_at' => $nextRun]);
                });
            });

        $dispatched = $this->dispatch();
        SiteIntelScheduledReport::query()->where('status', SiteIntelScheduledReport::COMPLETED)
            ->whereNull('completion_notified_at')->select('id')->orderBy('id')->limit($this->config->integer('dispatch_batch'))->get()
            ->each(fn ($report) => $this->generator->announce($report->id));

        return $dispatched;
    }

    public function createOccurrence(SiteIntelReportSchedule $schedule, CarbonImmutable $scheduledFor, bool $manual = false): void
    {
        $range = $this->period->range($schedule, $scheduledFor);
        foreach ($schedule->targets as $target) {
            SiteIntelScheduledReport::query()->firstOrCreate([
                'schedule_id' => $schedule->id, 'target_url' => $target, 'scheduled_for' => $scheduledFor,
            ], ['user_id' => $schedule->user_id, ...$range, 'status' => SiteIntelScheduledReport::PENDING,
                'report_type' => $schedule->report_type, 'crawl_limit' => $schedule->crawl_limit,
                'platform_type' => $schedule->platform_type,
                'is_manual' => $manual, 'available_at' => now()]);
        }
    }

    public function dispatch(): int
    {
        $this->config->ensureQueue();
        $reports = SiteIntelScheduledReport::query()->whereIn('status', [SiteIntelScheduledReport::PENDING, SiteIntelScheduledReport::PROCESSING])
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->leaseAvailable()->orderBy('id')->limit($this->config->integer('dispatch_batch'))->get();
        $count = 0;
        foreach ($reports as $report) {
            // Keep queued jobs valid across long backlogs. Only a crashed processing worker is fenced.
            $token = $report->status === SiteIntelScheduledReport::PENDING && $report->lease_token !== null
                ? $report->lease_token : (string) Str::uuid();
            $claimed = SiteIntelScheduledReport::query()->whereKey($report->id)
                ->where('status', $report->status)->where('lease_token', $report->lease_token)
                ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
                ->leaseAvailable()
                ->update(['status' => SiteIntelScheduledReport::PENDING, 'lease_token' => $token,
                    'lease_until' => now()->addSeconds($this->config->integer('lease_seconds'))]);
            if (! $claimed) {
                continue;
            }
            if ($report->attempt_count >= $this->config->integer('max_attempts')) {
                $this->generator->fail($report->id, $token, 'attempts_exhausted');

                continue;
            }
            try {
                Bus::dispatch(new GenerateSiteIntelReport($report->id, $token));
                $count++;
            } catch (Throwable $exception) {
                // A queue outage does not spend a generation attempt or lose the occurrence.
                SiteIntelScheduledReport::query()->whereKey($report->id)->where('lease_token', $token)
                    ->where('status', SiteIntelScheduledReport::PENDING)
                    ->update(['lease_token' => $report->status === SiteIntelScheduledReport::PENDING ? $report->lease_token : null,
                        'lease_until' => null,
                        'available_at' => now()->addSeconds($this->config->integer('retry_seconds'))]);
                Log::warning('SiteIntel report queue unavailable.', ['exception_class' => $exception::class]);
            }
        }

        return $count;
    }
}
