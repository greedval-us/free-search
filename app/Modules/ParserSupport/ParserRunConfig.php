<?php

namespace App\Modules\ParserSupport;

final readonly class ParserRunConfig
{
    private const DEFAULT_QUEUE = 'default';

    private const DEFAULT_STEP_DELAY_SECONDS = 2;

    private const DEFAULT_RETENTION_DAYS = 7;

    private const DEFAULT_HISTORY_LIMIT = 20;

    private const DEFAULT_CLEANUP_BATCH_SIZE = 500;

    private const DEFAULT_CLEANUP_SCHEDULE = '03:30';

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            queueEnabled: (bool) data_get($config, 'queue.enabled', true),
            queueName: self::nonEmptyString(data_get($config, 'queue.name'), self::DEFAULT_QUEUE),
            stepDelaySeconds: max(0, (int) data_get($config, 'queue.step_delay_seconds', self::DEFAULT_STEP_DELAY_SECONDS)),
            retentionDays: max(1, (int) ($config['retention_days'] ?? self::DEFAULT_RETENTION_DAYS)),
            historyLimit: max(1, (int) ($config['history_limit'] ?? self::DEFAULT_HISTORY_LIMIT)),
            cleanupBatchSize: max(1, (int) ($config['cleanup_batch_size'] ?? self::DEFAULT_CLEANUP_BATCH_SIZE)),
            cleanupSchedule: self::schedule($config['cleanup_schedule'] ?? null),
        );
    }

    public function __construct(
        private bool $queueEnabled,
        private string $queueName,
        private int $stepDelaySeconds,
        private int $retentionDays = self::DEFAULT_RETENTION_DAYS,
        private int $historyLimit = self::DEFAULT_HISTORY_LIMIT,
        private int $cleanupBatchSize = self::DEFAULT_CLEANUP_BATCH_SIZE,
        private string $cleanupSchedule = self::DEFAULT_CLEANUP_SCHEDULE,
    ) {}

    public function queueEnabled(): bool
    {
        return $this->queueEnabled;
    }

    public function queueName(): string
    {
        return $this->queueName;
    }

    public function stepDelaySeconds(): int
    {
        return $this->stepDelaySeconds;
    }

    public function retentionDays(): int
    {
        return $this->retentionDays;
    }

    public function historyLimit(): int
    {
        return $this->historyLimit;
    }

    public function cleanupBatchSize(): int
    {
        return $this->cleanupBatchSize;
    }

    public function cleanupSchedule(): string
    {
        return $this->cleanupSchedule;
    }

    private static function nonEmptyString(mixed $value, string $default): string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : $default;
    }

    private static function schedule(mixed $value): string
    {
        $schedule = self::nonEmptyString($value, self::DEFAULT_CLEANUP_SCHEDULE);

        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $schedule) === 1
            ? $schedule
            : self::DEFAULT_CLEANUP_SCHEDULE;
    }
}
