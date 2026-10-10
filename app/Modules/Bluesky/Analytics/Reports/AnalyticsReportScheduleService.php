<?php

namespace App\Modules\Bluesky\Analytics\Reports;

use App\Models\BlueskyAnalyticsSchedule;
use App\Models\User;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use App\Support\PublicBlueskyAccount;
use App\Support\Reports\Scheduling\ReportScheduleLifecycle;
use App\Support\Reports\Scheduling\ReportScheduleRules;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class AnalyticsReportScheduleService
{
    public function __construct(
        private AnalyticsReportConfig $config,
        private AnalyticsReportPeriod $period,
        private AnalyticsReportScheduler $scheduler,
        private FeatureAccessServiceInterface $access,
        private ReportScheduleLifecycle $lifecycle,
    ) {}

    public function create(User $user, array $data): BlueskyAnalyticsSchedule
    {
        $this->config->ensureQueue();
        $accounts = $this->accounts($data['accounts'] ?? []);
        $interval = (string) ($data['interval'] ?? '');
        $time = (string) ($data['send_time'] ?? '');
        $timezone = (string) ($data['timezone'] ?? config('bluesky_analytics_reports.timezone'));
        if (($reason = ReportScheduleRules::invalidReason($interval, $time, $timezone)) !== null) {
            throw new AnalyticsReportException($reason);
        }

        return DB::transaction(function () use ($user, $data, $accounts, $interval, $time, $timezone): BlueskyAnalyticsSchedule {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureAccess($locked);
            if (BlueskyAnalyticsSchedule::query()->forUser($locked->id)->count() >= $this->config->integer('max_schedules')) {
                throw new AnalyticsReportException('schedule_limit');
            }

            return BlueskyAnalyticsSchedule::query()->create([
                'user_id' => $locked->id, 'name' => $data['name'], 'accounts' => $accounts,
                'interval' => $interval, 'send_time' => $time, 'timezone' => $timezone,
                'send_to_bot' => (bool) ($data['send_to_bot'] ?? false), 'enabled' => true,
                'next_run_at' => $this->period->firstRun($interval, $time, $timezone),
            ]);
        });
    }

    public function change(User $user, int $id, string $action): void
    {
        $this->lifecycle->change($user, BlueskyAnalyticsSchedule::class, $id, $action,
            function (User $locked, BlueskyAnalyticsSchedule $schedule): void {
                $this->config->ensureQueue();
                $this->ensureAccess($locked);
                if (! $schedule->enabled) {
                    $schedule->update(['enabled' => true,
                        'next_run_at' => $this->period->firstRun($schedule->interval, $schedule->send_time, $schedule->timezone)]);
                }
            }, new AnalyticsReportException('invalid_action'));
    }

    public function runNow(User $user, int $id): void
    {
        $this->config->ensureQueue();
        DB::transaction(function () use ($user, $id): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureAccess($locked);
            $schedule = BlueskyAnalyticsSchedule::query()->forUser($locked->id)->lockForUpdate()->findOrFail($id);
            $this->scheduler->createOccurrence($schedule, CarbonImmutable::now()->startOfSecond()->utc(), true);
        });
        $this->scheduler->dispatch();
    }

    public function destroy(User $user, int $id): void
    {
        $this->lifecycle->destroy($user, BlueskyAnalyticsSchedule::class, $id);
    }

    private function ensureAccess(User $user): void
    {
        if ($user->isBlocked() || ! $user->hasVerifiedEmail()) {
            throw new AnalyticsReportException('account_unavailable');
        }
        if (! $this->access->inspect($user, 'bluesky.analytics', false)->allowed) {
            throw new AnalyticsReportException('access_denied');
        }
    }

    private function accounts(array $accounts): array
    {
        $normalized = [];
        foreach ($accounts as $account) {
            $username = PublicBlueskyAccount::normalize($account);
            if ($username === null) {
                throw new AnalyticsReportException('invalid_accounts');
            }
            $normalized[] = $username;
        }
        $normalized = array_values(array_unique($normalized));
        if ($normalized === [] || count($normalized) > $this->config->integer('max_accounts')) {
            throw new AnalyticsReportException('invalid_accounts');
        }

        return $normalized;
    }
}
