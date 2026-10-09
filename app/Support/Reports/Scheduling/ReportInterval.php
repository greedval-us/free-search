<?php

namespace App\Support\Reports\Scheduling;

final class ReportInterval
{
    public const DAILY = '1';

    public const THREE_DAYS = '3';

    public const WEEKLY = '7';

    public const MONTHLY = 'month';

    public const VALUES = [self::DAILY, self::THREE_DAYS, self::WEEKLY, self::MONTHLY];
}
