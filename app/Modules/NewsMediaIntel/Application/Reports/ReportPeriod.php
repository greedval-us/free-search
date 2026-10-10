<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Models\NewsMediaReportSchedule;
use App\Support\Reports\Scheduling\ReportCalendar;
use Carbon\CarbonImmutable;

final readonly class ReportPeriod
{
    public function __construct(private ReportCalendar $calendar) {}

    public function firstRun(string $interval, string $time, string $timezone): CarbonImmutable
    {
        return $this->calendar->firstRun($interval, $time, $timezone);
    }

    public function nextRun(NewsMediaReportSchedule $schedule, CarbonImmutable $scheduledFor): CarbonImmutable
    {
        return $this->calendar->nextRun($schedule->interval, $schedule->send_time, $schedule->timezone, $scheduledFor);
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    public function latestDue(NewsMediaReportSchedule $schedule): array
    {
        return $this->calendar->latestDue($schedule->interval, $schedule->send_time, $schedule->timezone, $schedule->next_run_at);
    }
}
