<?php

namespace App\Modules\Telegram\Analytics\Reports;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Models\TelegramAnalyticsReport;
use App\Models\User;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\Reports\Events\AnalyticsReportCompleted;
use App\Modules\Telegram\DTO\Request\TelegramAnalyticsParamsDTO;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class AnalyticsReportGenerator
{
    public function __construct(
        private TelegramAnalyticsApplicationServiceInterface $analytics,
        private FeatureAccessServiceInterface $access,
        private AnalyticsReportConfig $config,
    ) {}

    public function generate(int $reportId, string $token): void
    {
        $lock = Cache::lock('telegram-analytics-report:'.$reportId, $this->config->integer('lease_seconds'));
        if (! $lock->get()) {
            return;
        }
        try {
            $report = $this->prepare($reportId, $token);
            if ($report === null) {
                return;
            }
            $timezone = $report->schedule->timezone;
            $result = $this->analytics->buildSummary($report->user_id,
                new TelegramAnalyticsParamsDTO($report->chat_username, 'balanced', null),
                Carbon::instance($report->date_from)->setTimezone($timezone),
                Carbon::instance($report->date_to)->setTimezone($timezone));
            $completed = DB::transaction(function () use ($reportId, $token, $result): bool {
                $report = TelegramAnalyticsReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
                if ($report === null || $report->status !== TelegramAnalyticsReport::PROCESSING) {
                    return false;
                }
                // Revalidate changes made while the external calculation was in flight.
                $reason = $this->unavailableReason($report);
                if ($reason !== null) {
                    $this->failLocked($report, $reason);

                    return false;
                }
                $report->update(['status' => TelegramAnalyticsReport::COMPLETED, 'data' => $result->data,
                    'completed_at' => now(), 'error_code' => null, 'lease_token' => null, 'lease_until' => null]);

                return true;
            });
            if ($completed) {
                $this->announce($reportId);
            }
        } catch (Throwable $exception) {
            Log::warning('Telegram analytics report generation failed.', ['report_id' => $reportId, 'exception_class' => $exception::class]);
            if ($exception instanceof ExternalServiceRequestException && in_array($exception->errorCode(), [
                'telegram_analytics_collection_limit', 'telegram_analytics_pagination_stalled',
            ], true)) {
                $this->fail($reportId, $token, $exception->errorCode() === 'telegram_analytics_collection_limit' ? 'collection_limit' : 'pagination_stalled');
            } else {
                $this->retry($reportId, $token);
            }
        } finally {
            $lock->release();
        }
    }

    public function announce(int $reportId): void
    {
        $report = TelegramAnalyticsReport::query()->where('status', TelegramAnalyticsReport::COMPLETED)
            ->whereNull('completion_notified_at')->find($reportId);
        if ($report === null) {
            return;
        }
        try {
            // Bot listeners use the report ID as a durable deduplication key.
            Event::dispatch(new AnalyticsReportCompleted($reportId));
            TelegramAnalyticsReport::query()->whereKey($reportId)->update(['completion_notified_at' => now()]);
        } catch (Throwable $exception) {
            // The website report stays complete. Scheduler retries only the notification.
            Log::warning('Telegram analytics report completion notification failed.', ['report_id' => $reportId, 'exception_class' => $exception::class]);
        }
    }

    public function fail(int $reportId, string $token, string $reason): void
    {
        DB::transaction(function () use ($reportId, $token, $reason): void {
            $report = TelegramAnalyticsReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
            if ($report !== null && in_array($report->status, [TelegramAnalyticsReport::PENDING, TelegramAnalyticsReport::PROCESSING], true)) {
                $this->failLocked($report, $reason);
            }
        });
    }

    private function prepare(int $reportId, string $token): ?TelegramAnalyticsReport
    {
        return DB::transaction(function () use ($reportId, $token): ?TelegramAnalyticsReport {
            $snapshot = TelegramAnalyticsReport::query()->find($reportId);
            if ($snapshot === null) {
                return null;
            }
            User::query()->lockForUpdate()->find($snapshot->user_id);
            $report = TelegramAnalyticsReport::query()->where('lease_token', $token)
                ->lockForUpdate()->find($reportId);
            if ($report === null || ! in_array($report->status, [TelegramAnalyticsReport::PENDING, TelegramAnalyticsReport::PROCESSING], true)) {
                return null;
            }
            if ($report->status === TelegramAnalyticsReport::PROCESSING) {
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
                $decision = $this->access->consumeResource($report->user, 'telegram.analytics');
                if (! $decision->allowed) {
                    $this->failLocked($report, 'access_denied');

                    return null;
                }
                $report->quota_charged = true;
                $report->quota_charged_at = now();
            }
            $report->status = TelegramAnalyticsReport::PROCESSING;
            $report->attempt_count++;
            $report->lease_until = now()->addSeconds($this->config->integer('lease_seconds'));
            $report->save();

            return $report;
        });
    }

    private function unavailableReason(TelegramAnalyticsReport $report): ?string
    {
        $user = $report->user;
        $schedule = $report->schedule;
        if ($user === null || $user->isBlocked() || ! $user->hasVerifiedEmail()) {
            return 'account_unavailable';
        }
        if ($schedule === null || $schedule->trashed() || (! $schedule->enabled && ! $report->is_manual)) {
            return 'disabled';
        }

        return $this->access->inspect($user, 'telegram.analytics', false)->allowed ? null : 'access_denied';
    }

    private function retry(int $reportId, string $token): void
    {
        DB::transaction(function () use ($reportId, $token): void {
            $report = TelegramAnalyticsReport::query()->where('lease_token', $token)->lockForUpdate()->find($reportId);
            if ($report === null || ! in_array($report->status, [TelegramAnalyticsReport::PENDING, TelegramAnalyticsReport::PROCESSING], true)) {
                return;
            }
            if ($report->attempt_count >= $this->config->integer('max_attempts')) {
                $this->failLocked($report, 'generation_failed');

                return;
            }
            $report->update(['status' => TelegramAnalyticsReport::PENDING, 'error_code' => 'generation_failed',
                'available_at' => now()->addSeconds($this->config->integer('retry_seconds') * max(1, $report->attempt_count)),
                'lease_token' => null, 'lease_until' => null]);
        });
    }

    private function failLocked(TelegramAnalyticsReport $report, string $reason): void
    {
        if ($report->quota_charged && $report->user !== null && $report->quota_charged_at !== null
            && $report->quota_charged_at->setTimezone(config('app.timezone'))->toDateString() === now(config('app.timezone'))->toDateString()) {
            $this->access->refundResource($report->user, 'telegram.analytics');
        }
        $report->update(['status' => TelegramAnalyticsReport::FAILED, 'error_code' => $reason,
            'quota_charged' => false, 'lease_token' => null, 'lease_until' => null]);
    }
}
