<?php

namespace App\Modules\YouTube\Support;

final class YouTubeModuleConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config, string $timezone): self
    {
        $periodDays = self::intList($config['analytics_period_days'] ?? [1, 3, 7], [1, 3, 7]);
        $periodDays = array_values(array_unique(array_filter($periodDays, static fn (int $value): bool => $value > 0)));
        if ($periodDays === []) {
            $periodDays = [1, 3, 7];
        }

        $defaultPeriodDays = self::intValue($config['analytics_default_period_days'] ?? null, 7);
        if (!in_array($defaultPeriodDays, $periodDays, true)) {
            $defaultPeriodDays = max($periodDays);
        }

        $parserDefaultLimit = max(1, self::intValue($config['parser_comments_limit_default'] ?? null, 20));
        $parserMaxLimit = max($parserDefaultLimit, self::intValue($config['parser_comments_limit_max'] ?? null, 100));
        $searchDefaultLimit = max(1, self::intValue($config['search_limit_default'] ?? null, 10));
        $searchMaxLimit = max($searchDefaultLimit, self::intValue($config['search_limit_max'] ?? null, 10));

        return new self(
            timezone: trim($timezone) !== '' ? $timezone : 'UTC',
            analyticsPeriodDays: $periodDays,
            analyticsDefaultPeriodDays: $defaultPeriodDays,
            analyticsCustomRangeMaxDays: max(1, self::intValue($config['analytics_custom_range_max_days'] ?? null, 7)),
            parserCommentsLimitDefault: $parserDefaultLimit,
            parserCommentsLimitMax: $parserMaxLimit,
            searchLimitDefault: $searchDefaultLimit,
            searchLimitMax: $searchMaxLimit,
        );
    }

    /**
     * @param array<int, int> $analyticsPeriodDays
     */
    public function __construct(
        private readonly string $timezone,
        private readonly array $analyticsPeriodDays,
        private readonly int $analyticsDefaultPeriodDays,
        private readonly int $analyticsCustomRangeMaxDays,
        private readonly int $parserCommentsLimitDefault,
        private readonly int $parserCommentsLimitMax,
        private readonly int $searchLimitDefault,
        private readonly int $searchLimitMax,
    ) {
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    /**
     * @return array<int, int>
     */
    public function analyticsPeriodDays(): array
    {
        return $this->analyticsPeriodDays;
    }

    public function analyticsDefaultPeriodDays(): int
    {
        return $this->analyticsDefaultPeriodDays;
    }

    public function analyticsCustomRangeMaxDays(): int
    {
        return $this->analyticsCustomRangeMaxDays;
    }

    public function parserCommentsLimitDefault(): int
    {
        return $this->parserCommentsLimitDefault;
    }

    public function parserCommentsLimitMax(): int
    {
        return $this->parserCommentsLimitMax;
    }

    public function searchLimitDefault(): int
    {
        return $this->searchLimitDefault;
    }

    public function searchLimitMax(): int
    {
        return $this->searchLimitMax;
    }

    /**
     * @param mixed $value
     */
    private static function intValue(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param mixed $value
     * @param array<int, int> $default
     * @return array<int, int>
     */
    private static function intList(mixed $value, array $default): array
    {
        if (!is_array($value)) {
            return $default;
        }

        $items = [];
        foreach ($value as $raw) {
            if (is_numeric($raw)) {
                $items[] = (int) $raw;
            }
        }

        return $items !== [] ? $items : $default;
    }
}
