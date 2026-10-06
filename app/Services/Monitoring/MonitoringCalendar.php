<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringSchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class MonitoringCalendar
{
    public const PERIODS = ['day', 'three_days', 'week', 'month'];

    /** Calendar boundaries are local; persistence always uses UTC and [start,end). */
    public function period(string $period, string $timezone, ?CarbonImmutable $at = null): array
    {
        $day = ($at ?? CarbonImmutable::now('UTC'))->setTimezone($timezone)->startOfDay();
        [$start, $end] = match ($period) {
            'day' => [$day->subDay(), $day],
            'three_days' => [$day->subDays(3), $day],
            'week' => [$day->startOfWeek(CarbonInterface::MONDAY)->subWeek(), $day->startOfWeek(CarbonInterface::MONDAY)],
            'month' => [$day->startOfMonth()->subMonthNoOverflow(), $day->startOfMonth()],
            default => throw new InvalidArgumentException('Unsupported monitoring period'),
        };

        return [$start->utc(), $end->utc()];
    }

    public function next(MonitoringSchedule $schedule, CarbonImmutable $after): CarbonImmutable
    {
        $local = $after->setTimezone($schedule->timezone);
        $date = $local->startOfDay();
        $anchor = CarbonImmutable::parse($schedule->anchor_date->format('Y-m-d'), $schedule->timezone);
        for ($i = 0; $i < 400; $i++, $date = $date->addDay()) {
            $days = (int) $anchor->diffInDays($date, false);
            $due = match ($schedule->period) {
                'day' => true,
                'three_days' => (($days % 3) + 3) % 3 === 0,
                'week' => $date->isMonday(),
                'month' => $date->day === 1,
                default => false,
            };
            $candidate = CarbonImmutable::parse($date->format('Y-m-d').' '.$schedule->time, $schedule->timezone);
            if ($due && $candidate->gt($local)) {
                return $candidate->utc();
            }
        }
        throw new InvalidArgumentException('Unsupported monitoring schedule');
    }

    public function previous(MonitoringSchedule $schedule, CarbonImmutable $at): CarbonImmutable
    {
        $local = $at->setTimezone($schedule->timezone);
        $date = $local->startOfDay();
        $anchor = CarbonImmutable::parse($schedule->anchor_date->format('Y-m-d'), $schedule->timezone);
        for ($i = 0; $i < 40; $i++, $date = $date->subDay()) {
            $days = (int) $anchor->diffInDays($date, false);
            $due = match ($schedule->period) {
                'day' => true, 'three_days' => (($days % 3) + 3) % 3 === 0,
                'week' => $date->isMonday(), 'month' => $date->day === 1, default => false,
            };
            $candidate = CarbonImmutable::parse($date->format('Y-m-d').' '.$schedule->time, $schedule->timezone);
            if ($due && $candidate->lte($local)) {
                return $candidate->utc();
            }
        }
        throw new InvalidArgumentException('Unsupported monitoring schedule');
    }

    public function skipped(MonitoringSchedule $schedule, CarbonImmutable $first, CarbonImmutable $last): int
    {
        $first = $first->setTimezone($schedule->timezone)->startOfDay();
        $last = $last->setTimezone($schedule->timezone)->startOfDay();

        return max(0, match ($schedule->period) {
            'month' => ($last->year - $first->year) * 12 + $last->month - $first->month,
            'week' => (int) ($first->diffInDays($last) / 7),
            'three_days' => (int) ($first->diffInDays($last) / 3),
            default => (int) $first->diffInDays($last),
        });
    }
}
