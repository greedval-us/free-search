<?php

namespace App\Modules\Mastodon\Analytics\Reports;

use App\Models\MastodonAnalyticsSchedule;
use App\Support\Reports\Scheduling\ReportCalendar;
use Carbon\CarbonImmutable;

final readonly class AnalyticsReportPeriod
{
    public function __construct(private ReportCalendar $calendar) {}

    public function firstRun(string $interval, string $time, string $timezone): CarbonImmutable
    {
        return $this->calendar->firstRun($interval, $time, $timezone);
    }

    public function nextRun(MastodonAnalyticsSchedule $schedule, CarbonImmutable $scheduledFor): CarbonImmutable
    {
        return $this->calendar->nextRun($schedule->interval, $schedule->send_time, $schedule->timezone, $scheduledFor);
    }

    /** @return array{date_from: CarbonImmutable, date_to: CarbonImmutable} */
    public function range(MastodonAnalyticsSchedule $schedule, CarbonImmutable $scheduledFor): array
    {
        return $this->calendar->historyRange($schedule->interval, $schedule->timezone, $scheduledFor);
    }
}
