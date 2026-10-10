<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Models\SiteIntelReportSchedule;
use App\Support\Reports\Scheduling\ReportCalendar;
use Carbon\CarbonImmutable;

final readonly class ReportPeriod
{
    public function __construct(private ReportCalendar $calendar) {}

    public function firstRun(string $interval, string $time, string $timezone): CarbonImmutable
    {
        return $this->calendar->firstRun($interval, $time, $timezone);
    }

    public function nextRun(SiteIntelReportSchedule $schedule, CarbonImmutable $scheduledFor): CarbonImmutable
    {
        return $this->calendar->nextRun($schedule->interval, $schedule->send_time, $schedule->timezone, $scheduledFor);
    }

    /** @return array{date_from: CarbonImmutable, date_to: CarbonImmutable} */
    public function range(SiteIntelReportSchedule $schedule, CarbonImmutable $scheduledFor): array
    {
        return $this->calendar->historyRange($schedule->interval, $schedule->timezone, $scheduledFor);
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    public function latestDue(SiteIntelReportSchedule $schedule): array
    {
        return $this->calendar->latestDue($schedule->interval, $schedule->send_time, $schedule->timezone, $schedule->next_run_at);
    }
}
