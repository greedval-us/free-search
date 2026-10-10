<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Models\NewsMediaReportSchedule;
use App\Models\User;
use App\Support\Reports\Scheduling\ReportScheduleLifecycle;
use App\Support\Reports\Scheduling\ReportScheduleRules;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class ReportScheduleService
{
    public function __construct(
        private ReportConfig $config,
        private ReportPeriod $period,
        private ReportScheduler $scheduler,
        private ReportParameters $parameters,
        private ReportScheduleLifecycle $lifecycle,
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
        if (($reason = ReportScheduleRules::invalidReason($interval, $time, $timezone)) !== null) {
            throw new ReportException($reason);
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
        $this->lifecycle->change($user, NewsMediaReportSchedule::class, $id, $action,
            function (User $locked, NewsMediaReportSchedule $schedule): void {
                $this->config->ensureQueue();
                $this->ensureAccount($locked);
                if (! $schedule->enabled) {
                    $schedule->update(['enabled' => true,
                        'next_run_at' => $this->period->firstRun($schedule->interval, $schedule->send_time, $schedule->timezone)]);
                }
            }, new ReportException('invalid_action'));
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
        $this->lifecycle->destroy($user, NewsMediaReportSchedule::class, $id);
    }

    private function ensureAccount(User $user): void
    {
        if ($user->isBlocked() || ! $user->hasVerifiedEmail()) {
            throw new ReportException('account_unavailable');
        }
    }
}
