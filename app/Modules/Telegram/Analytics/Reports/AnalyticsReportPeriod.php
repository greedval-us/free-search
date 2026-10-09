<?php

namespace App\Modules\Telegram\Analytics\Reports;

use App\Models\TelegramAnalyticsSchedule;
use Carbon\CarbonImmutable;

final class AnalyticsReportPeriod
{
    public function firstRun(string $interval, string $time, string $timezone): CarbonImmutable
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $date = $interval === 'month' ? $today->startOfMonth()->addMonthNoOverflow() : $today->addDays((int) $interval);

        return $date->setTimeFromTimeString($time)->utc();
    }

    public function nextRun(TelegramAnalyticsSchedule $schedule, CarbonImmutable $scheduledFor): CarbonImmutable
    {
        $local = $scheduledFor->setTimezone($schedule->timezone);
        $next = $schedule->interval === 'month'
            ? $local->startOfMonth()->addMonthNoOverflow()
            : $local->addDays((int) $schedule->interval);

        return $next->setTimeFromTimeString($schedule->send_time)->startOfSecond()->utc();
    }

    /** @return array{date_from: CarbonImmutable, date_to: CarbonImmutable} */
    public function range(TelegramAnalyticsSchedule $schedule, CarbonImmutable $scheduledFor): array
    {
        $local = $scheduledFor->setTimezone($schedule->timezone);
        $end = $schedule->interval === 'month' ? $local->startOfMonth()->subSecond() : $local->startOfDay()->subSecond();
        $start = $schedule->interval === 'month'
            ? $end->startOfMonth()
            : $local->startOfDay()->subDays((int) $schedule->interval);

        return ['date_from' => $start->utc(), 'date_to' => $end->utc()];
    }
}
