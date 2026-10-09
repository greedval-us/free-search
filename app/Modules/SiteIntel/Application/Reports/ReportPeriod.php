<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Models\SiteIntelReportSchedule;
use Carbon\CarbonImmutable;

final class ReportPeriod
{
    public function firstRun(string $interval, string $time, string $timezone): CarbonImmutable
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $date = $interval === 'month' ? $today->startOfMonth()->addMonthNoOverflow() : $today->addDays((int) $interval);

        return $date->setTimeFromTimeString($time)->utc();
    }

    public function nextRun(SiteIntelReportSchedule $schedule, CarbonImmutable $scheduledFor): CarbonImmutable
    {
        $local = $scheduledFor->setTimezone($schedule->timezone);
        $next = $schedule->interval === 'month'
            ? $local->startOfMonth()->addMonthNoOverflow()
            : $local->addDays((int) $schedule->interval);

        return $next->setTimeFromTimeString($schedule->send_time)->startOfSecond()->utc();
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    public function latestDue(SiteIntelReportSchedule $schedule): array
    {
        $first = $schedule->next_run_at->setTimezone($schedule->timezone);
        $now = CarbonImmutable::now($schedule->timezone);
        if ($schedule->interval === 'month') {
            $months = ($now->year - $first->year) * 12 + $now->month - $first->month;
            $latest = $first->addMonthsNoOverflow(max(0, $months))->setTimeFromTimeString($schedule->send_time);
            if ($latest->gt($now)) {
                $latest = $latest->subMonthNoOverflow()->setTimeFromTimeString($schedule->send_time);
            }
        } else {
            $days = (int) $first->startOfDay()->diffInDays($now->startOfDay());
            $steps = max(0, intdiv($days, (int) $schedule->interval));
            $latest = $first->addDays($steps * (int) $schedule->interval)->setTimeFromTimeString($schedule->send_time);
            if ($latest->gt($now)) {
                $latest = $latest->subDays((int) $schedule->interval)->setTimeFromTimeString($schedule->send_time);
            }
        }
        $latest = $latest->setTimeFromTimeString($schedule->send_time)->startOfSecond()->utc();

        return [$latest, $this->nextRun($schedule, $latest)];
    }

    /** @return array{date_from: CarbonImmutable, date_to: CarbonImmutable} */
    public function range(SiteIntelReportSchedule $schedule, CarbonImmutable $scheduledFor): array
    {
        $local = $scheduledFor->setTimezone($schedule->timezone);
        $end = $schedule->interval === 'month' ? $local->startOfMonth()->subSecond() : $local->startOfDay()->subSecond();
        $start = $schedule->interval === 'month'
            ? $end->startOfMonth()
            : $local->startOfDay()->subDays((int) $schedule->interval);

        return ['date_from' => $start->utc(), 'date_to' => $end->utc()];
    }
}
