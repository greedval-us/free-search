<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Models\NewsMediaReportSchedule;
use Carbon\CarbonImmutable;

final class ReportPeriod
{
    public function firstRun(string $interval, string $time, string $timezone): CarbonImmutable
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $date = $interval === 'month' ? $today->startOfMonth()->addMonthNoOverflow() : $today->addDays((int) $interval);

        return $date->setTimeFromTimeString($time)->utc();
    }

    public function nextRun(NewsMediaReportSchedule $schedule, CarbonImmutable $scheduledFor): CarbonImmutable
    {
        $local = $scheduledFor->setTimezone($schedule->timezone);
        $next = $schedule->interval === 'month'
            ? $local->startOfMonth()->addMonthNoOverflow()
            : $local->addDays((int) $schedule->interval);

        return $next->setTimeFromTimeString($schedule->send_time)->startOfSecond()->utc();
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    public function latestDue(NewsMediaReportSchedule $schedule): array
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
}
