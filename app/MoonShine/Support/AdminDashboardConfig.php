<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

final readonly class AdminDashboardConfig
{
    /**
     * @param  list<int>  $periods
     */
    private function __construct(
        public int $defaultPeriod,
        public array $periods,
        public int $topModulesLimit,
        public int $slowResponseMilliseconds,
        public int $queueBacklogWarning,
        public float $errorRateWarningPercent,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $periods = array_values(array_unique(array_filter(
            array_map('intval', (array) ($config['periods'] ?? [7, 30, 90])),
            static fn (int $period): bool => $period > 0,
        )));

        if ($periods === []) {
            $periods = [7, 30, 90];
        }

        $defaultPeriod = max(1, (int) ($config['default_period'] ?? $periods[0]));
        if (! in_array($defaultPeriod, $periods, true)) {
            $defaultPeriod = $periods[0];
        }

        return new self(
            defaultPeriod: $defaultPeriod,
            periods: $periods,
            topModulesLimit: max(1, (int) ($config['top_modules_limit'] ?? 6)),
            slowResponseMilliseconds: max(1, (int) ($config['slow_response_ms'] ?? 1500)),
            queueBacklogWarning: max(1, (int) ($config['queue_backlog_warning'] ?? 25)),
            errorRateWarningPercent: max(0.0, (float) ($config['error_rate_warning_percent'] ?? 2.0)),
        );
    }

    public function normalizePeriod(int $period): int
    {
        return in_array($period, $this->periods, true) ? $period : $this->defaultPeriod;
    }
}
