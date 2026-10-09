<?php

namespace Tests\Feature;

use App\Support\Reports\Scheduling\ReportCalendar;
use App\Support\Reports\Scheduling\ReportInterval;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportCalendarTest extends TestCase
{
    public static function deliveryIntervals(): array
    {
        return [
            'daily' => [ReportInterval::DAILY, '2026-10-10 06:00:00', '2026-10-11 06:00:00'],
            'three days' => [ReportInterval::THREE_DAYS, '2026-10-12 06:00:00', '2026-10-15 06:00:00'],
            'weekly' => [ReportInterval::WEEKLY, '2026-10-16 06:00:00', '2026-10-23 06:00:00'],
            'calendar month' => [ReportInterval::MONTHLY, '2026-11-01 06:00:00', '2026-12-01 06:00:00'],
        ];
    }

    #[DataProvider('deliveryIntervals')]
    public function test_delivery_intervals_use_selected_local_time_and_return_utc(string $interval, string $expectedFirst, string $expectedNext): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
        $calendar = new ReportCalendar;

        $first = $calendar->firstRun($interval, '09:00', 'Europe/Moscow');
        $next = $calendar->nextRun($interval, '09:00', 'Europe/Moscow', $first);

        $this->assertSame($expectedFirst, $first->format('Y-m-d H:i:s'));
        $this->assertSame($expectedNext, $next->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $first->timezoneName);
        $this->assertSame('UTC', $next->timezoneName);
    }

    public static function historyWindows(): array
    {
        return [
            'spring day has 23 hours' => [ReportInterval::DAILY, 'Europe/Berlin', '2026-03-30 07:00:00',
                '2026-03-28 23:00:00', '2026-03-29 21:59:59'],
            'autumn day has 25 hours' => [ReportInterval::DAILY, 'Europe/Berlin', '2026-10-26 08:00:00',
                '2026-10-24 22:00:00', '2026-10-25 22:59:59'],
            'three complete local days across DST' => [ReportInterval::THREE_DAYS, 'Europe/Berlin', '2026-03-30 07:00:00',
                '2026-03-26 23:00:00', '2026-03-29 21:59:59'],
            'seven complete local days across DST' => [ReportInterval::WEEKLY, 'Europe/Berlin', '2026-03-30 07:00:00',
                '2026-03-22 23:00:00', '2026-03-29 21:59:59'],
            'leap calendar month' => [ReportInterval::MONTHLY, 'Europe/Moscow', '2028-03-01 06:00:00',
                '2028-01-31 21:00:00', '2028-02-29 20:59:59'],
            'negative UTC offset' => [ReportInterval::DAILY, 'America/New_York', '2026-10-09 13:00:00',
                '2026-10-08 04:00:00', '2026-10-09 03:59:59'],
        ];
    }

    #[DataProvider('historyWindows')]
    public function test_history_windows_cover_complete_local_days_and_calendar_months(string $interval, string $timezone, string $scheduledFor, string $from, string $to): void
    {
        $range = (new ReportCalendar)->historyRange($interval, $timezone, CarbonImmutable::parse($scheduledFor, 'UTC'));

        $this->assertSame($from, $range['date_from']->format('Y-m-d H:i:s'));
        $this->assertSame($to, $range['date_to']->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $range['date_from']->timezoneName);
        $this->assertSame('UTC', $range['date_to']->timezoneName);
    }

    public function test_next_delivery_keeps_wall_clock_time_across_dst_and_month_does_not_overflow(): void
    {
        $calendar = new ReportCalendar;
        $spring = $calendar->nextRun(ReportInterval::DAILY, '09:00', 'Europe/Berlin', CarbonImmutable::parse('2026-03-28 08:00:00', 'UTC'));
        $autumn = $calendar->nextRun(ReportInterval::DAILY, '09:00', 'Europe/Berlin', CarbonImmutable::parse('2026-10-24 07:00:00', 'UTC'));
        $month = $calendar->nextRun(ReportInterval::MONTHLY, '09:00', 'Europe/Moscow', CarbonImmutable::parse('2028-01-31 06:00:00', 'UTC'));

        $this->assertSame('2026-03-29 07:00:00', $spring->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-25 08:00:00', $autumn->format('Y-m-d H:i:s'));
        $this->assertSame('2028-02-01 06:00:00', $month->format('Y-m-d H:i:s'));
    }

    public static function missedSnapshotRuns(): array
    {
        return [
            'daily before local delivery' => [ReportInterval::DAILY, 'Europe/Moscow', '09:00', '2026-10-10 06:00:00',
                '2026-10-15 05:59:00', '2026-10-14 06:00:00', '2026-10-15 06:00:00'],
            'daily at local delivery' => [ReportInterval::DAILY, 'Europe/Moscow', '09:00', '2026-10-10 06:00:00',
                '2026-10-15 06:00:00', '2026-10-15 06:00:00', '2026-10-16 06:00:00'],
            'monthly before local delivery' => [ReportInterval::MONTHLY, 'Europe/Moscow', '09:00', '2026-11-01 06:00:00',
                '2028-03-01 05:59:00', '2028-02-01 06:00:00', '2028-03-01 06:00:00'],
            'daily after first DST gap' => [ReportInterval::DAILY, 'Europe/Berlin', '02:30', '2026-03-29 01:30:00',
                '2026-03-30 00:45:00', '2026-03-30 00:30:00', '2026-03-31 00:30:00'],
            'three days after first DST gap' => [ReportInterval::THREE_DAYS, 'Europe/Berlin', '02:30', '2026-03-29 01:30:00',
                '2026-04-01 00:45:00', '2026-04-01 00:30:00', '2026-04-04 00:30:00'],
        ];
    }

    #[DataProvider('missedSnapshotRuns')]
    public function test_snapshot_coalescing_selects_latest_due_run_and_restores_local_time(string $interval, string $timezone, string $time, string $first, string $now, string $expectedDue, string $expectedNext): void
    {
        $this->travelTo(CarbonImmutable::parse($now, 'UTC'));

        [$due, $next] = (new ReportCalendar)->latestDue($interval, $time, $timezone, CarbonImmutable::parse($first, 'UTC'));

        $this->assertSame($expectedDue, $due->format('Y-m-d H:i:s'));
        $this->assertSame($expectedNext, $next->format('Y-m-d H:i:s'));
        $this->assertTrue($due->lte(now()));
        $this->assertTrue($next->gt(now()));
    }
}
