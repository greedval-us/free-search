<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Models\SiteIntelReportSchedule;
use App\Models\User;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

final readonly class ReportScheduleService
{
    public function __construct(
        private ReportConfig $config,
        private ReportPeriod $period,
        private ReportScheduler $scheduler,
        private FeatureAccessServiceInterface $access,
    ) {}

    public function create(User $user, array $data): SiteIntelReportSchedule
    {
        $this->config->ensureQueue();
        $targets = $this->targets($data['targets'] ?? []);
        $type = (string) ($data['report_type'] ?? '');
        $crawlLimit = filter_var($data['crawl_limit'] ?? 8, FILTER_VALIDATE_INT);
        $platformType = (string) ($data['platform_type'] ?? 'auto');
        if (! in_array($type, SiteIntelReportSchedule::TYPES, true)) {
            throw new ReportException('invalid_type');
        }
        if ($crawlLimit === false || $crawlLimit < 3 || $crawlLimit > 20
            || ! in_array($platformType, SiteIntelReportSchedule::PLATFORM_TYPES, true)) {
            throw new ReportException('invalid_options');
        }
        $interval = (string) ($data['interval'] ?? '');
        $time = (string) ($data['send_time'] ?? '');
        $timezone = (string) ($data['timezone'] ?? config('site_intel_reports.timezone'));
        if (! in_array($interval, SiteIntelReportSchedule::INTERVALS, true)) {
            throw new ReportException('invalid_interval');
        }
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time) !== 1) {
            throw new ReportException('invalid_time');
        }
        if (! in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            throw new ReportException('invalid_timezone');
        }

        return DB::transaction(function () use ($user, $data, $targets, $type, $crawlLimit, $platformType, $interval, $time, $timezone): SiteIntelReportSchedule {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureAccess($locked, $type);
            if (SiteIntelReportSchedule::query()->forUser($locked->id)->count() >= $this->config->integer('max_schedules')) {
                throw new ReportException('schedule_limit');
            }

            return SiteIntelReportSchedule::query()->create([
                'user_id' => $locked->id, 'name' => $data['name'], 'targets' => $targets,
                'report_type' => $type, 'crawl_limit' => $crawlLimit, 'platform_type' => $platformType,
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
            $schedule = SiteIntelReportSchedule::query()->forUser($locked->id)->lockForUpdate()->findOrFail($id);
            if ($action === 'pause') {
                $schedule->update(['enabled' => false]);

                return;
            }
            if ($action !== 'resume') {
                throw new ReportException('invalid_action');
            }
            $this->config->ensureQueue();
            $this->ensureAccess($locked, $schedule->report_type);
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
            $schedule = SiteIntelReportSchedule::query()->forUser($locked->id)->lockForUpdate()->findOrFail($id);
            $this->ensureAccess($locked, $schedule->report_type);
            $this->scheduler->createOccurrence($schedule, CarbonImmutable::now()->startOfSecond()->utc(), true);
        });
        $this->scheduler->dispatch();
    }

    public function destroy(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $schedule = SiteIntelReportSchedule::query()->forUser($user->id)->lockForUpdate()->findOrFail($id);
            $schedule->update(['enabled' => false]);
            $schedule->delete();
        });
    }

    private function ensureAccess(User $user, string $type): void
    {
        if ($user->isBlocked() || ! $user->hasVerifiedEmail()) {
            throw new ReportException('account_unavailable');
        }
        if (! $this->access->inspect($user, 'site-intel.'.$type, false)->allowed) {
            throw new ReportException('access_denied');
        }
    }

    private function targets(array $targets): array
    {
        $normalized = [];
        foreach ($targets as $target) {
            $url = PublicSiteTarget::normalize($target);
            if ($url === null) {
                throw new ReportException('invalid_targets');
            }
            $normalized[] = $url;
        }
        $normalized = array_values(array_unique($normalized));
        if ($normalized === [] || count($normalized) > $this->config->maxTargets()) {
            throw new ReportException('invalid_targets');
        }

        return $normalized;
    }
}
