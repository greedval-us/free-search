<?php

namespace App\Modules\Telegram\Analytics\Reports;

use App\Models\TelegramAnalyticsSchedule;
use App\Models\User;
use App\Modules\Telegram\Access\PublicTelegramSource;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

final readonly class AnalyticsReportScheduleService
{
    public function __construct(
        private AnalyticsReportConfig $config,
        private AnalyticsReportPeriod $period,
        private AnalyticsReportScheduler $scheduler,
        private FeatureAccessServiceInterface $access,
    ) {}

    public function create(User $user, array $data): TelegramAnalyticsSchedule
    {
        $this->config->ensureQueue();
        $groups = $this->groups($data['groups'] ?? []);
        $interval = (string) ($data['interval'] ?? '');
        $time = (string) ($data['send_time'] ?? '');
        $timezone = (string) ($data['timezone'] ?? config('telegram_analytics_reports.timezone'));
        if (! in_array($interval, TelegramAnalyticsSchedule::INTERVALS, true)) {
            throw new AnalyticsReportException('invalid_interval');
        }
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time) !== 1) {
            throw new AnalyticsReportException('invalid_time');
        }
        if (! in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            throw new AnalyticsReportException('invalid_timezone');
        }

        return DB::transaction(function () use ($user, $data, $groups, $interval, $time, $timezone): TelegramAnalyticsSchedule {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureAccess($locked);
            if (TelegramAnalyticsSchedule::query()->forUser($locked->id)->count() >= $this->config->integer('max_schedules')) {
                throw new AnalyticsReportException('schedule_limit');
            }

            return TelegramAnalyticsSchedule::query()->create([
                'user_id' => $locked->id, 'name' => $data['name'], 'groups' => $groups,
                'interval' => $interval, 'send_time' => $time, 'timezone' => $timezone,
                'send_to_bot' => (bool) ($data['send_to_bot'] ?? false), 'enabled' => true,
                'next_run_at' => $this->period->firstRun($interval, $time, $timezone),
            ]);
        });
    }

    public function change(User $user, int $id, string $action): void
    {
        DB::transaction(function () use ($user, $id, $action): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $schedule = TelegramAnalyticsSchedule::query()->forUser($locked->id)->lockForUpdate()->findOrFail($id);
            if ($action === 'pause') {
                $schedule->update(['enabled' => false]);

                return;
            }
            if ($action !== 'resume') {
                throw new AnalyticsReportException('invalid_action');
            }
            $this->config->ensureQueue();
            $this->ensureAccess($locked);
            if (! $schedule->enabled) {
                $schedule->update(['enabled' => true,
                    'next_run_at' => $this->period->firstRun($schedule->interval, $schedule->send_time, $schedule->timezone)]);
            }
        });
    }

    public function runNow(User $user, int $id): void
    {
        $this->config->ensureQueue();
        DB::transaction(function () use ($user, $id): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureAccess($locked);
            $schedule = TelegramAnalyticsSchedule::query()->forUser($locked->id)->lockForUpdate()->findOrFail($id);
            $this->scheduler->createOccurrence($schedule, CarbonImmutable::now()->startOfSecond()->utc(), true);
        });
        $this->scheduler->dispatch();
    }

    public function destroy(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $schedule = TelegramAnalyticsSchedule::query()->forUser($user->id)->lockForUpdate()->findOrFail($id);
            $schedule->update(['enabled' => false]);
            $schedule->delete();
        });
    }

    private function ensureAccess(User $user): void
    {
        if ($user->isBlocked() || ! $user->hasVerifiedEmail()) {
            throw new AnalyticsReportException('account_unavailable');
        }
        if (! $this->access->inspect($user, 'telegram.analytics', false)->allowed) {
            throw new AnalyticsReportException('access_denied');
        }
    }

    private function groups(array $groups): array
    {
        $normalized = [];
        foreach ($groups as $group) {
            $username = PublicTelegramSource::username($group);
            if ($username === null) {
                throw new AnalyticsReportException('invalid_groups');
            }
            $normalized[] = $username;
        }
        $normalized = array_values(array_unique($normalized));
        if ($normalized === [] || count($normalized) > $this->config->integer('max_groups')) {
            throw new AnalyticsReportException('invalid_groups');
        }

        return $normalized;
    }
}
