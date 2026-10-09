<?php

namespace App\Support\Reports\Scheduling;

use Carbon\CarbonImmutable;

/** Calendar arithmetic shared by report schedules; does not choose catch-up policy. */
final class ReportCalendar
{
    public function firstRun(string $interval, string $time, string $timezone): CarbonImmutable
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $date = $interval === ReportInterval::MONTHLY
            ? $today->startOfMonth()->addMonthNoOverflow()
            : $today->addDays((int) $interval);

        return $date->setTimeFromTimeString($time)->utc();
    }

    public function nextRun(string $interval, string $time, string $timezone, CarbonImmutable $scheduledFor): CarbonImmutable
    {
        $local = $scheduledFor->setTimezone($timezone);
        $next = $interval === ReportInterval::MONTHLY
            ? $local->startOfMonth()->addMonthNoOverflow()
            : $local->addDays((int) $interval);

        return $next->setTimeFromTimeString($time)->startOfSecond()->utc();
    }

    /** @return array{date_from: CarbonImmutable, date_to: CarbonImmutable} */
    public function historyRange(string $interval, string $timezone, CarbonImmutable $scheduledFor): array
    {
        $local = $scheduledFor->setTimezone($timezone);
        $end = $interval === ReportInterval::MONTHLY
            ? $local->startOfMonth()->subSecond()
            : $local->startOfDay()->subSecond();
        $start = $interval === ReportInterval::MONTHLY
            ? $end->startOfMonth()
            : $local->startOfDay()->subDays((int) $interval);

        return ['date_from' => $start->utc(), 'date_to' => $end->utc()];
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    public function latestDue(string $interval, string $time, string $timezone, CarbonImmutable $firstDue): array
    {
        $first = $firstDue->setTimezone($timezone);
        $now = CarbonImmutable::now($timezone);
        if ($interval === ReportInterval::MONTHLY) {
            $months = ($now->year - $first->year) * 12 + $now->month - $first->month;
            $latest = $first->addMonthsNoOverflow(max(0, $months))->setTimeFromTimeString($time);
            if ($latest->gt($now)) {
                $latest = $latest->subMonthNoOverflow()->setTimeFromTimeString($time);
            }
        } else {
            $days = (int) $first->startOfDay()->diffInDays($now->startOfDay());
            $steps = max(0, intdiv($days, (int) $interval));
            $latest = $first->addDays($steps * (int) $interval)->setTimeFromTimeString($time);
            if ($latest->gt($now)) {
                $latest = $latest->subDays((int) $interval)->setTimeFromTimeString($time);
            }
        }
        // Restore the selected wall-clock time after a first occurrence in a DST gap.
        $latest = $latest->setTimeFromTimeString($time)->startOfSecond()->utc();

        return [$latest, $this->nextRun($interval, $time, $timezone, $latest)];
    }
}
