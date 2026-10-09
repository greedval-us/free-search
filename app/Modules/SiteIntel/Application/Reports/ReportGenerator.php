<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Exceptions\Public\PublicValidationException;
use App\Models\SiteIntelReportSchedule;
use App\Models\SiteIntelScheduledReport;
use App\Models\User;
use App\Modules\SiteIntel\Application\Reports\Events\SiteIntelReportCompleted;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ReportGenerator
{
    public function __construct(
        private ReportSourceBuilder $source,
        private FeatureAccessServiceInterface $access,
        private ReportConfig $config,
    ) {}

    public function generate(int $reportId, string $token): void
    {
        $lock = Cache::lock('site-intel-report:'.$reportId, $this->config->integer('lease_seconds'));
        if (! $lock->get()) {
            return;
        }
        try {
            $report = $this->prepare($reportId, $token);
            if ($report === null) {
                return;
            }
            $result = $this->source->build($report);
            $completed = DB::transaction(function () use ($reportId, $token, $result): bool {
                $report = SiteIntelScheduledReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
                if ($report === null || $report->status !== SiteIntelScheduledReport::PROCESSING) {
                    return false;
                }
                // Revalidate changes made while the external calculation was in flight.
                $reason = $this->unavailableReason($report);
                if ($reason !== null) {
                    $this->failLocked($report, $reason);

                    return false;
                }
                $report->update(['status' => SiteIntelScheduledReport::COMPLETED, 'data' => $result,
                    'completed_at' => now(), 'error_code' => null, 'lease_token' => null, 'lease_until' => null]);

                return true;
            });
            if ($completed) {
                $this->announce($reportId);
            }
        } catch (Throwable $exception) {
            Log::warning('SiteIntel report generation failed.', ['report_id' => $reportId, 'exception_class' => $exception::class]);
            if ($exception instanceof PublicValidationException && $exception->errorCode() === 'site_intel_invalid_target') {
                $this->fail($reportId, $token, 'invalid_target');
            } elseif ($exception instanceof ReportException) {
                $this->fail($reportId, $token, $exception->reason);
            } else {
                $this->retry($reportId, $token);
            }
        } finally {
            $lock->release();
        }
    }

    public function announce(int $reportId): void
    {
        $report = SiteIntelScheduledReport::query()->where('status', SiteIntelScheduledReport::COMPLETED)
            ->whereNull('completion_notified_at')->find($reportId);
        if ($report === null) {
            return;
        }
        try {
            // Bot listeners use the report ID as a durable deduplication key.
            Event::dispatch(new SiteIntelReportCompleted($reportId));
            SiteIntelScheduledReport::query()->whereKey($reportId)->update(['completion_notified_at' => now()]);
        } catch (Throwable $exception) {
            // The website report stays complete. Scheduler retries only the notification.
            Log::warning('SiteIntel report completion notification failed.', ['report_id' => $reportId, 'exception_class' => $exception::class]);
        }
    }

    public function fail(int $reportId, string $token, string $reason): void
    {
        DB::transaction(function () use ($reportId, $token, $reason): void {
            $report = SiteIntelScheduledReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
            if ($report !== null && in_array($report->status, [SiteIntelScheduledReport::PENDING, SiteIntelScheduledReport::PROCESSING], true)) {
                $this->failLocked($report, $reason);
            }
        });
    }

    private function prepare(int $reportId, string $token): ?SiteIntelScheduledReport
    {
        return DB::transaction(function () use ($reportId, $token): ?SiteIntelScheduledReport {
            $snapshot = SiteIntelScheduledReport::query()->find($reportId);
            if ($snapshot === null) {
                return null;
            }
            User::query()->lockForUpdate()->find($snapshot->user_id);
            $report = SiteIntelScheduledReport::query()->where('lease_token', $token)
                ->lockForUpdate()->find($reportId);
            if ($report === null || ! in_array($report->status, [SiteIntelScheduledReport::PENDING, SiteIntelScheduledReport::PROCESSING], true)) {
                return null;
            }
            if ($report->status === SiteIntelScheduledReport::PROCESSING) {
                return null;
            }
            if ($report->attempt_count >= $this->config->integer('max_attempts')) {
                $this->failLocked($report, 'attempts_exhausted');

                return null;
            }
            $reason = $this->unavailableReason($report);
            if ($reason !== null) {
                $this->failLocked($report, $reason);

                return null;
            }
            if (! $report->quota_charged) {
                $decision = $this->access->consumeResource($report->user, $report->resourceKey());
                if (! $decision->allowed) {
                    $this->failLocked($report, 'access_denied');

                    return null;
                }
                $report->quota_charged = true;
                $report->quota_charged_at = now();
            }
            $report->status = SiteIntelScheduledReport::PROCESSING;
            $report->attempt_count++;
            $report->lease_until = now()->addSeconds($this->config->integer('lease_seconds'));
            $report->save();

            return $report;
        });
    }

    private function unavailableReason(SiteIntelScheduledReport $report): ?string
    {
        $user = $report->user;
        $schedule = $report->schedule;
        if ($user === null || $user->isBlocked() || ! $user->hasVerifiedEmail()) {
            return 'account_unavailable';
        }
        if ($schedule === null || $schedule->user_id !== $report->user_id || $schedule->trashed()
            || (! $schedule->enabled && ! $report->is_manual)) {
            return 'disabled';
        }

        if (! in_array($report->report_type, SiteIntelReportSchedule::TYPES, true)) {
            return 'invalid_type';
        }

        return $this->access->inspect($user, $report->resourceKey(), false)->allowed ? null : 'access_denied';
    }

    private function retry(int $reportId, string $token): void
    {
        DB::transaction(function () use ($reportId, $token): void {
            $report = SiteIntelScheduledReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
            if ($report === null || ! in_array($report->status, [SiteIntelScheduledReport::PENDING, SiteIntelScheduledReport::PROCESSING], true)) {
                return;
            }
            if ($report->attempt_count >= $this->config->integer('max_attempts')) {
                $this->failLocked($report, 'generation_failed');

                return;
            }
            $report->update(['status' => SiteIntelScheduledReport::PENDING, 'error_code' => 'generation_failed',
                'available_at' => now()->addSeconds($this->config->integer('retry_seconds') * max(1, $report->attempt_count)),
                'lease_token' => null, 'lease_until' => null]);
        });
    }

    private function failLocked(SiteIntelScheduledReport $report, string $reason): void
    {
        if ($report->quota_charged && $report->user !== null && $report->quota_charged_at !== null
            && $report->quota_charged_at->setTimezone(config('app.timezone'))->toDateString() === now(config('app.timezone'))->toDateString()) {
            $this->access->refundResource($report->user, $report->resourceKey());
        }
        $report->update(['status' => SiteIntelScheduledReport::FAILED, 'error_code' => $reason,
            'quota_charged' => false, 'lease_token' => null, 'lease_until' => null]);
    }
}
