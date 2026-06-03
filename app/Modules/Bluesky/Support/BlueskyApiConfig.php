<?php

namespace App\Modules\Bluesky\Support;

final class BlueskyApiConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            identifier: trim(self::stringValue($config, 'identifier', '')),
            appPassword: trim(self::stringValue($config, 'app_password', '')),
            pdsUrl: rtrim(self::stringValue($config, 'pds_url', 'https://bsky.social'), '/'),
            timeoutSeconds: max(1, self::intValue($config, 'timeout_seconds', 20)),
            retryAttempts: max(0, self::intValue($config, 'retry_attempts', 2)),
            retryDelayMilliseconds: max(0, self::intValue($config, 'retry_delay_milliseconds', 250)),
        );
    }

    public function __construct(
        private readonly string $identifier,
        private readonly string $appPassword,
        private readonly string $pdsUrl,
        private readonly int $timeoutSeconds,
        private readonly int $retryAttempts,
        private readonly int $retryDelayMilliseconds,
    ) {
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function appPassword(): string
    {
        return $this->appPassword;
    }

    public function pdsUrl(): string
    {
        return $this->pdsUrl;
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
