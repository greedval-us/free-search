<?php

declare(strict_types=1);

namespace Tests\Unit\MoonShine;

use App\MoonShine\Support\AdminDashboardConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AdminDashboardConfigTest extends TestCase
{
    #[Test]
    public function it_normalizes_periods_and_thresholds(): void
    {
        $config = AdminDashboardConfig::fromArray([
            'default_period' => 30,
            'periods' => [7, 30, 30, -1],
            'top_modules_limit' => 0,
            'slow_response_ms' => 0,
            'queue_backlog_warning' => 0,
            'error_rate_warning_percent' => -3,
        ]);

        self::assertSame([7, 30], $config->periods);
        self::assertSame(30, $config->normalizePeriod(30));
        self::assertSame(30, $config->normalizePeriod(365));
        self::assertSame(1, $config->topModulesLimit);
        self::assertSame(1, $config->slowResponseMilliseconds);
        self::assertSame(1, $config->queueBacklogWarning);
        self::assertSame(0.0, $config->errorRateWarningPercent);
    }
}
