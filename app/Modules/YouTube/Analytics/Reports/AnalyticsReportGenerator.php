<?php

namespace App\Modules\YouTube\Analytics\Reports;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Models\User;
use App\Models\YouTubeAnalyticsReport;
use App\Modules\YouTube\Analytics\Reports\Events\AnalyticsReportCompleted;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class AnalyticsReportGenerator
{
    public function __construct(
        private ScheduledYouTubeAnalytics $analytics,
        private FeatureAccessServiceInterface $access,
        private AnalyticsReportConfig $config,
    ) {}

    public function generate(int $reportId, string $token): void
    {
        $lock = Cache::lock('youtube-analytics-report:'.$reportId, $this->config->integer('lease_seconds'));
        if (! $lock->get()) {
            return;
        }
        try {
            $report = $this->prepare($reportId, $token);
            if ($report === null) {
                return;
            }
            $timezone = $report->schedule->timezone;
            $result = $this->analytics->build($report->channel_input, $report->date_from->utc(),
                $report->date_to->utc(), $timezone);
            $completed = DB::transaction(function () use ($reportId, $token, $result): bool {
                $report = YouTubeAnalyticsReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
                if ($report === null || $report->status !== YouTubeAnalyticsReport::PROCESSING) {
                    return false;
                }
                // Revalidate changes made while the external calculation was in flight.
                $reason = $this->unavailableReason($report);
                if ($reason !== null) {
                    $this->failLocked($report, $reason);

                    return false;
                }
                $report->update(['status' => YouTubeAnalyticsReport::COMPLETED, 'data' => $result,
                    'completed_at' => now(), 'error_code' => null, 'lease_token' => null, 'lease_until' => null]);

                return true;
            });
            if ($completed) {
                $this->announce($reportId);
            }
        } catch (Throwable $exception) {
            Log::warning('YouTube analytics report generation failed.', ['report_id' => $reportId, 'exception_class' => $exception::class]);
            if ($exception instanceof ExternalServiceRequestException && in_array($exception->errorCode(), [
                'youtube_analytics_collection_limit', 'youtube_analytics_pagination_stalled',
            ], true)) {
                $this->fail($reportId, $token, $exception->errorCode() === 'youtube_analytics_collection_limit' ? 'collection_limit' : 'pagination_stalled');
            } else {
                $this->retry($reportId, $token);
            }
        } finally {
            $lock->release();
        }
    }

    public function announce(int $reportId): void
    {
        $report = YouTubeAnalyticsReport::query()->where('status', YouTubeAnalyticsReport::COMPLETED)
            ->whereNull('completion_notified_at')->find($reportId);
        if ($report === null) {
            return;
        }
        try {
            // Bot listeners use the report ID as a durable deduplication key.
            Event::dispatch(new AnalyticsReportCompleted($reportId));
            YouTubeAnalyticsReport::query()->whereKey($reportId)->update(['completion_notified_at' => now()]);
        } catch (Throwable $exception) {
            // The website report stays complete. Scheduler retries only the notification.
            Log::warning('YouTube analytics report completion notification failed.', ['report_id' => $reportId, 'exception_class' => $exception::class]);
        }
    }

    public function fail(int $reportId, string $token, string $reason): void
    {
        DB::transaction(function () use ($reportId, $token, $reason): void {
            $report = YouTubeAnalyticsReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
            if ($report !== null && in_array($report->status, [YouTubeAnalyticsReport::PENDING, YouTubeAnalyticsReport::PROCESSING], true)) {
                $this->failLocked($report, $reason);
            }
        });
    }

    private function prepare(int $reportId, string $token): ?YouTubeAnalyticsReport
    {
        return DB::transaction(function () use ($reportId, $token): ?YouTubeAnalyticsReport {
            $snapshot = YouTubeAnalyticsReport::query()->find($reportId);
            if ($snapshot === null) {
                return null;
            }
            User::query()->lockForUpdate()->find($snapshot->user_id);
            $report = YouTubeAnalyticsReport::query()->where('lease_token', $token)
                ->lockForUpdate()->find($reportId);
            if ($report === null || ! in_array($report->status, [YouTubeAnalyticsReport::PENDING, YouTubeAnalyticsReport::PROCESSING], true)) {
                return null;
            }
            if ($report->status === YouTubeAnalyticsReport::PROCESSING) {
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
                $decision = $this->access->consumeResource($report->user, 'youtube.analytics');
                if (! $decision->allowed) {
                    $this->failLocked($report, 'access_denied');

                    return null;
                }
                $report->quota_charged = true;
                $report->quota_charged_at = now();
            }
            $report->status = YouTubeAnalyticsReport::PROCESSING;
            $report->attempt_count++;
            $report->lease_until = now()->addSeconds($this->config->integer('lease_seconds'));
            $report->save();

            return $report;
        });
    }

    private function unavailableReason(YouTubeAnalyticsReport $report): ?string
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

        return $this->access->inspect($user, 'youtube.analytics', false)->allowed ? null : 'access_denied';
    }

    private function retry(int $reportId, string $token): void
    {
        DB::transaction(function () use ($reportId, $token): void {
            $report = YouTubeAnalyticsReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
            if ($report === null || ! in_array($report->status, [YouTubeAnalyticsReport::PENDING, YouTubeAnalyticsReport::PROCESSING], true)) {
                return;
            }
            if ($report->attempt_count >= $this->config->integer('max_attempts')) {
                $this->failLocked($report, 'generation_failed');

                return;
            }
            $report->update(['status' => YouTubeAnalyticsReport::PENDING, 'error_code' => 'generation_failed',
                'available_at' => now()->addSeconds($this->config->integer('retry_seconds') * max(1, $report->attempt_count)),
                'lease_token' => null, 'lease_until' => null]);
        });
    }

    private function failLocked(YouTubeAnalyticsReport $report, string $reason): void
    {
        if ($report->quota_charged && $report->user !== null && $report->quota_charged_at !== null
            && $report->quota_charged_at->setTimezone(config('app.timezone'))->toDateString() === now(config('app.timezone'))->toDateString()) {
            $this->access->refundResource($report->user, 'youtube.analytics');
        }
        $report->update(['status' => YouTubeAnalyticsReport::FAILED, 'error_code' => $reason,
            'quota_charged' => false, 'lease_token' => null, 'lease_until' => null]);
    }
}
