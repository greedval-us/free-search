<?php

namespace Tests\Unit;

use App\Models\MonitoringSchedule;
use App\Services\Monitoring\MonitoringCalendar;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MonitoringCalendarTest extends TestCase
{
    public static function periods(): array
    {
        return [
            ['day', 'Europe/Berlin', '2026-03-30T10:00:00Z', '2026-03-28T23:00:00+00:00', '2026-03-29T22:00:00+00:00'],
            ['day', 'Europe/Berlin', '2026-10-26T10:00:00Z', '2026-10-24T22:00:00+00:00', '2026-10-25T23:00:00+00:00'],
            ['three_days', 'Europe/Moscow', '2026-01-02T10:00:00Z', '2025-12-29T21:00:00+00:00', '2026-01-01T21:00:00+00:00'],
            ['week', 'UTC', '2026-01-05T10:00:00Z', '2025-12-29T00:00:00+00:00', '2026-01-05T00:00:00+00:00'],
            ['month', 'UTC', '2024-03-15T10:00:00Z', '2024-02-01T00:00:00+00:00', '2024-03-01T00:00:00+00:00'],
            ['month', 'UTC', '2025-03-01T10:00:00Z', '2025-02-01T00:00:00+00:00', '2025-03-01T00:00:00+00:00'],
        ];
    }

    #[DataProvider('periods')]
    public function test_calendar_half_open_periods(string $period, string $zone, string $now, string $start, string $end): void
    {
        [$from, $until] = (new MonitoringCalendar)->period($period, $zone, CarbonImmutable::parse($now));
        $this->assertSame($start, $from->toIso8601String());
        $this->assertSame($end, $until->toIso8601String());
    }

    public function test_three_day_schedule_uses_saved_anchor_and_week_month_use_calendar_dates(): void
    {
        $calendar = new MonitoringCalendar;
        $schedule = new MonitoringSchedule(['period' => 'three_days', 'time' => '09:00', 'timezone' => 'Europe/Moscow', 'anchor_date' => '2026-10-01']);
        $this->assertSame('2026-10-07T06:00:00+00:00', $calendar->next($schedule, CarbonImmutable::parse('2026-10-05T12:00:00Z'))->toIso8601String());
        $schedule->period = 'month';
        $this->assertSame('2026-11-01T06:00:00+00:00', $calendar->next($schedule, CarbonImmutable::parse('2026-10-05T12:00:00Z'))->toIso8601String());
        $schedule->period = 'week';
        $this->assertSame('2026-10-12T06:00:00+00:00', $calendar->next($schedule, CarbonImmutable::parse('2026-10-05T12:00:00Z'))->toIso8601String());
    }
}
