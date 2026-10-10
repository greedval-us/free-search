<?php

namespace App\Modules\YouTube\Analytics\Reports;

use App\Models\User;
use App\Models\YouTubeAnalyticsSchedule;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
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

    public function create(User $user, array $data): YouTubeAnalyticsSchedule
    {
        $this->config->ensureQueue();
        $channels = $this->channels($data['channels'] ?? []);
        $interval = (string) ($data['interval'] ?? '');
        $time = (string) ($data['send_time'] ?? '');
        $timezone = (string) ($data['timezone'] ?? config('youtube_analytics_reports.timezone'));
        if (($reason = ReportScheduleRules::invalidReason($interval, $time, $timezone)) !== null) {
            throw new AnalyticsReportException($reason);
        }

        return DB::transaction(function () use ($user, $data, $channels, $interval, $time, $timezone): YouTubeAnalyticsSchedule {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureAccess($locked);
            if (YouTubeAnalyticsSchedule::query()->forUser($locked->id)->count() >= $this->config->integer('max_schedules')) {
                throw new AnalyticsReportException('schedule_limit');
            }

            return YouTubeAnalyticsSchedule::query()->create([
                'user_id' => $locked->id, 'name' => $data['name'], 'channels' => $channels,
                'interval' => $interval, 'send_time' => $time, 'timezone' => $timezone,
                'send_to_bot' => (bool) ($data['send_to_bot'] ?? false), 'enabled' => true,
                'next_run_at' => $this->period->firstRun($interval, $time, $timezone),
            ]);
        });
    }

    public function change(User $user, int $id, string $action): void
    {
        $this->lifecycle->change($user, YouTubeAnalyticsSchedule::class, $id, $action,
            function (User $locked, YouTubeAnalyticsSchedule $schedule): void {
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
            $schedule = YouTubeAnalyticsSchedule::query()->forUser($locked->id)->lockForUpdate()->findOrFail($id);
            $this->scheduler->createOccurrence($schedule, CarbonImmutable::now()->startOfSecond()->utc(), true);
        });
        $this->scheduler->dispatch();
    }

    public function destroy(User $user, int $id): void
    {
        $this->lifecycle->destroy($user, YouTubeAnalyticsSchedule::class, $id);
    }

    private function ensureAccess(User $user): void
    {
        if ($user->isBlocked() || ! $user->hasVerifiedEmail()) {
            throw new AnalyticsReportException('account_unavailable');
        }
        if (! $this->access->inspect($user, 'youtube.analytics', false)->allowed) {
            throw new AnalyticsReportException('access_denied');
        }
    }

    private function channels(array $channels): array
    {
        $normalized = [];
        foreach ($channels as $channel) {
            $username = PublicYouTubeChannel::normalize($channel);
            if ($username === null) {
                throw new AnalyticsReportException('invalid_channels');
            }
            $normalized[] = $username;
        }
        $normalized = array_values(array_unique($normalized));
        if ($normalized === [] || count($normalized) > $this->config->integer('max_channels')) {
            throw new AnalyticsReportException('invalid_channels');
        }

        return $normalized;
    }
}
