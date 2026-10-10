<?php

namespace App\Support\Reports\Scheduling;

use DateTimeZone;

final class ReportScheduleRules
{
    /** Module adapters retain their input normalization and public exception type. */
    public static function invalidReason(mixed $interval, mixed $time, mixed $timezone): ?string
    {
        if (! in_array($interval, ReportInterval::VALUES, true)) {
            return 'invalid_interval';
        }
        if (! is_string($time) || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time) !== 1) {
            return 'invalid_time';
        }
        if (! in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            return 'invalid_timezone';
        }

        return null;
    }
}
