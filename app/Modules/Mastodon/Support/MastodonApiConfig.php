<?php

namespace App\Modules\Mastodon\Support;

final class MastodonApiConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            apiToken: trim(self::stringValue($config, 'token', '')),
            baseUrl: rtrim(
                self::stringValue($config, 'base_url', 'https://mastodon.social'),
                '/'
            ),
            timeoutSeconds: max(1, self::intValue($config, 'timeout_seconds', 20)),
            retryAttempts: max(0, self::intValue($config, 'retry_attempts', 2)),
            retryDelayMilliseconds: max(0, self::intValue($config, 'retry_delay_milliseconds', 250)),
        );
    }

    public function __construct(
        private readonly string $apiToken,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
        private readonly int $retryAttempts,
        private readonly int $retryDelayMilliseconds,
    ) {
    }

    public function apiToken(): string
    {
        return $this->apiToken;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function retryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function retryDelayMilliseconds(): int
    {
        return $this->retryDelayMilliseconds;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function stringValue(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function intValue(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }
}
