<?php

namespace App\Modules\Mastodon\Support;

final class MastodonModuleConfig
{
    private const DEFAULT_TIMEZONE = 'UTC';

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config, string $timezone = self::DEFAULT_TIMEZONE): self
    {
        return new self(
            timezone: trim($timezone) !== '' ? $timezone : self::DEFAULT_TIMEZONE,
            searchLimitDefault: max(1, self::intValue($config, 'search_limit_default', 10)),
            searchLimitMax: max(1, self::intValue($config, 'search_limit_max', 20)),
            defaultType: self::stringValue($config, 'default_type', 'statuses'),
        );
    }

    public function __construct(
        private readonly string $timezone,
        private readonly int $searchLimitDefault,
        private readonly int $searchLimitMax,
        private readonly string $defaultType,
    ) {}

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function searchLimitDefault(): int
    {
        return min($this->searchLimitDefault, $this->searchLimitMax);
    }

    public function searchLimitMax(): int
    {
        return $this->searchLimitMax;
    }

    public function defaultType(): string
    {
        return $this->defaultType;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function stringValue(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function intValue(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }
}
