<?php

namespace App\Modules\ParserSupport;

use App\Jobs\ProcessParserRun;

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
            recoveryBatchSize: max(1, (int) data_get($config, 'recovery.batch_size', 100)),
            recoveryStaleAfterSeconds: max(ProcessParserRun::EXECUTION_LOCK_SECONDS, (int) data_get($config, 'recovery.stale_after_seconds', 150)),
            maxStepAttempts: max(1, (int) data_get($config, 'limits.max_step_attempts', 10000)),
            maxRecords: max(1, (int) data_get($config, 'limits.max_records', 100000)),
            maxDurationSeconds: max(1, (int) data_get($config, 'limits.max_duration_seconds', 86400)),
            maxCheckpointBytes: max(4096, (int) data_get($config, 'limits.max_checkpoint_bytes', 33554432)),
            maxExportBytes: max(1, (int) data_get($config, 'limits.max_export_bytes', 67108864)),
            maxExportCells: max(1, (int) data_get($config, 'limits.max_export_cells', 1000000)),
            maxSourceRequests: max(1, (int) data_get($config, 'limits.max_source_requests', 100000)),
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
        private int $recoveryBatchSize = 100,
        private int $recoveryStaleAfterSeconds = 150,
        private int $maxStepAttempts = 10000,
        private int $maxRecords = 100000,
        private int $maxDurationSeconds = 86400,
        private int $maxCheckpointBytes = 33554432,
        private int $maxExportBytes = 67108864,
        private int $maxExportCells = 1000000,
        private int $maxSourceRequests = 100000,
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

    public function recoveryBatchSize(): int
    {
        return $this->recoveryBatchSize;
    }

    public function recoveryStaleAfterSeconds(): int
    {
        return max(ProcessParserRun::EXECUTION_LOCK_SECONDS, $this->recoveryStaleAfterSeconds, $this->stepDelaySeconds + 30);
    }

    public function maxStepAttempts(): int
    {
        return $this->maxStepAttempts;
    }

    public function maxRecords(): int
    {
        return $this->maxRecords;
    }

    public function maxDurationSeconds(): int
    {
        return $this->maxDurationSeconds;
    }

    public function maxCheckpointBytes(): int
    {
        return $this->maxCheckpointBytes;
    }

    public function maxExportBytes(): int
    {
        return $this->maxExportBytes;
    }

    public function maxExportCells(): int
    {
        return $this->maxExportCells;
    }

    public function maxSourceRequests(): int
    {
        return $this->maxSourceRequests;
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
