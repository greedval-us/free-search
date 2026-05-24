<?php

namespace App\Modules\Fio\Application\Support;

final class FioHttpConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $rawUserAgent = self::stringAt($config, 'user_agent');

        return new self(
            baseUserAgent: $rawUserAgent ?? 'FreeSearch-FIO/1.0',
            multiSourceUserAgent: $rawUserAgent ?? 'FreeSearch-FIO/2.0',
            timeoutSeconds: max(1, self::intAt($config, 'timeout_seconds', 12)),
            retryAttempts: max(0, self::intAt($config, 'retry_attempts', 1)),
            retrySleepMilliseconds: max(0, self::intAt($config, 'retry_sleep_milliseconds', 250)),
        );
    }

    public function __construct(
        private readonly string $baseUserAgent,
        private readonly string $multiSourceUserAgent,
        private readonly int $timeoutSeconds,
        private readonly int $retryAttempts,
        private readonly int $retrySleepMilliseconds,
    ) {
    }

    public function baseUserAgent(): string
    {
        return $this->baseUserAgent;
    }

    public function multiSourceUserAgent(): string
    {
        return $this->multiSourceUserAgent;
    }

    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function retryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function retrySleepMilliseconds(): int
    {
        return $this->retrySleepMilliseconds;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function stringAt(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function intAt(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }
}
