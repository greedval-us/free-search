<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Models\NewsMediaScheduledReport;
use App\Models\User;
use App\Modules\NewsMediaIntel\Application\Reports\Events\NewsMediaReportCompleted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class ReportGenerator
{
    public function __construct(
        private ReportSourceBuilder $source,
        private ReportConfig $config,
    ) {}

    public function generate(int $reportId, string $token): void
    {
        $lock = Cache::lock('news-media-report:'.$reportId, $this->config->integer('lease_seconds'));
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
                $report = NewsMediaScheduledReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
                if ($report === null || $report->status !== NewsMediaScheduledReport::PROCESSING) {
                    return false;
                }
                // Revalidate changes made while the external calculation was in flight.
                $reason = $this->unavailableReason($report);
                if ($reason !== null) {
                    $this->failLocked($report, $reason);

                    return false;
                }
                $report->update(['status' => NewsMediaScheduledReport::COMPLETED, 'data' => $result,
                    'completed_at' => now(), 'error_code' => null, 'lease_token' => null, 'lease_until' => null]);

                return true;
            });
            if ($completed) {
                $this->announce($reportId);
            }
        } catch (Throwable $exception) {
            Log::warning('News media report generation failed.', ['report_id' => $reportId, 'exception_class' => $exception::class]);
            if ($exception instanceof ValidationException) {
                $this->fail($reportId, $token, 'invalid_options');
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
        $report = NewsMediaScheduledReport::query()->where('status', NewsMediaScheduledReport::COMPLETED)
            ->whereNull('completion_notified_at')->find($reportId);
        if ($report === null) {
            return;
        }
        try {
            // Bot listeners use the report ID as a durable deduplication key.
            Event::dispatch(new NewsMediaReportCompleted($reportId));
            NewsMediaScheduledReport::query()->whereKey($reportId)->update(['completion_notified_at' => now()]);
        } catch (Throwable $exception) {
            // The website report stays complete. Scheduler retries only the notification.
            Log::warning('News media report completion notification failed.', ['report_id' => $reportId, 'exception_class' => $exception::class]);
        }
    }

    public function fail(int $reportId, string $token, string $reason): void
    {
        DB::transaction(function () use ($reportId, $token, $reason): void {
            $report = NewsMediaScheduledReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
            if ($report !== null && in_array($report->status, [NewsMediaScheduledReport::PENDING, NewsMediaScheduledReport::PROCESSING], true)) {
                $this->failLocked($report, $reason);
            }
        });
    }

    private function prepare(int $reportId, string $token): ?NewsMediaScheduledReport
    {
        return DB::transaction(function () use ($reportId, $token): ?NewsMediaScheduledReport {
            $snapshot = NewsMediaScheduledReport::query()->find($reportId);
            if ($snapshot === null) {
                return null;
            }
            User::query()->lockForUpdate()->find($snapshot->user_id);
            $report = NewsMediaScheduledReport::query()->where('lease_token', $token)
                ->lockForUpdate()->find($reportId);
            if ($report === null || ! in_array($report->status, [NewsMediaScheduledReport::PENDING, NewsMediaScheduledReport::PROCESSING], true)) {
                return null;
            }
            if ($report->status === NewsMediaScheduledReport::PROCESSING) {
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
            $report->status = NewsMediaScheduledReport::PROCESSING;
            $report->attempt_count++;
            $report->lease_until = now()->addSeconds($this->config->integer('lease_seconds'));
            $report->save();

            return $report;
        });
    }

    private function unavailableReason(NewsMediaScheduledReport $report): ?string
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

        return null;
    }

    private function retry(int $reportId, string $token): void
    {
        DB::transaction(function () use ($reportId, $token): void {
            $report = NewsMediaScheduledReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
            if ($report === null || ! in_array($report->status, [NewsMediaScheduledReport::PENDING, NewsMediaScheduledReport::PROCESSING], true)) {
                return;
            }
            if ($report->attempt_count >= $this->config->integer('max_attempts')) {
                $this->failLocked($report, 'generation_failed');

                return;
            }
            $report->update(['status' => NewsMediaScheduledReport::PENDING, 'error_code' => 'generation_failed',
                'available_at' => now()->addSeconds($this->config->integer('retry_seconds') * max(1, $report->attempt_count)),
                'lease_token' => null, 'lease_until' => null]);
        });
    }

    private function failLocked(NewsMediaScheduledReport $report, string $reason): void
    {
        $report->update(['status' => NewsMediaScheduledReport::FAILED, 'error_code' => $reason,
            'lease_token' => null, 'lease_until' => null]);
    }
}
