<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Models\NewsMediaReportSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

final readonly class ReportScheduleService
{
    public function __construct(
        private ReportConfig $config,
        private ReportPeriod $period,
        private ReportScheduler $scheduler,
        private ReportParameters $parameters,
    ) {}

    public function create(User $user, array $data): NewsMediaReportSchedule
    {
        $this->config->ensureQueue();
        $parameters = $this->parameters->normalize($data);
        $name = $data['name'] ?? null;
        if (! is_string($name) || trim($name) === '' || mb_strlen(trim($name)) > 100 || preg_match('/[\p{Cc}]/u', $name) !== 0) {
            throw new ReportException('invalid_options');
        }
        $interval = $data['interval'] ?? null;
        $time = $data['send_time'] ?? null;
        $timezone = $data['timezone'] ?? config('news_media_reports.timezone');
        if (! in_array($interval, NewsMediaReportSchedule::INTERVALS, true)) {
            throw new ReportException('invalid_interval');
        }
        if (! is_string($time) || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time) !== 1) {
            throw new ReportException('invalid_time');
        }
        if (! in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            throw new ReportException('invalid_timezone');
        }

        return DB::transaction(function () use ($user, $data, $parameters, $name, $interval, $time, $timezone): NewsMediaReportSchedule {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureAccount($locked);
            if (NewsMediaReportSchedule::query()->forUser($locked->id)->count() >= $this->config->integer('max_schedules')) {
                throw new ReportException('schedule_limit');
            }

            return NewsMediaReportSchedule::query()->create([
                'user_id' => $locked->id, 'name' => trim($name), ...$parameters,
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
            $schedule = NewsMediaReportSchedule::query()->forUser($locked->id)->lockForUpdate()->findOrFail($id);
            if ($action === 'pause') {
                $schedule->update(['enabled' => false]);

                return;
            }
            if ($action !== 'resume') {
                throw new ReportException('invalid_action');
            }
            $this->config->ensureQueue();
            $this->ensureAccount($locked);
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
            $schedule = NewsMediaReportSchedule::query()->forUser($locked->id)->lockForUpdate()->findOrFail($id);
            $this->ensureAccount($locked);
            $this->scheduler->createOccurrence($schedule, CarbonImmutable::now()->startOfSecond()->utc(), true);
        });
        $this->scheduler->dispatch();
    }

    public function destroy(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $schedule = NewsMediaReportSchedule::query()->forUser($user->id)->lockForUpdate()->findOrFail($id);
            $schedule->update(['enabled' => false]);
            $schedule->delete();
        });
    }

    private function ensureAccount(User $user): void
    {
        if ($user->isBlocked() || ! $user->hasVerifiedEmail()) {
            throw new ReportException('account_unavailable');
        }
    }
}
