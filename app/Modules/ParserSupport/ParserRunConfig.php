<?php

namespace App\Modules\ParserSupport;

final readonly class ParserRunConfig
{
    public const JOB_TIMEOUT_SECONDS = 120;

    public const LOCK_GRACE_SECONDS = 30;

    public const EXECUTION_LOCK_SECONDS = self::JOB_TIMEOUT_SECONDS + self::LOCK_GRACE_SECONDS;

    public const STEP_RETRY_WINDOW_SECONDS = 3600;

    public const MIN_CHECKPOINT_BYTES = 4096;

    public const MAX_RECOVERY_PASS_SECONDS = 3600;

    private const RECOVERY_MUTEX_MARGIN_MINUTES = 2;

    private const RECOVERY_STEP_GRACE_SECONDS = 30;

    public const DEFAULT_QUEUE = 'default';

    public const DEFAULT_STEP_DELAY_SECONDS = 2;

    public const DEFAULT_RETENTION_DAYS = 7;

    public const DEFAULT_HISTORY_LIMIT = 20;

    public const DEFAULT_CLEANUP_BATCH_SIZE = 500;

    public const DEFAULT_CLEANUP_SCHEDULE = '03:30';

    public const DEFAULT_RECOVERY_BATCH_SIZE = 100;

    public const DEFAULT_MAX_STEP_ATTEMPTS = 10_000;

    public const DEFAULT_MAX_RECORDS = 100_000;

    public const DEFAULT_MAX_DURATION_SECONDS = 86_400;

    public const DEFAULT_MAX_CHECKPOINT_BYTES = 33_554_432;

    public const DEFAULT_MAX_EXPORT_BYTES = 67_108_864;

    public const DEFAULT_MAX_EXPORT_CELLS = 1_000_000;

    public const DEFAULT_MAX_SOURCE_REQUESTS = 100_000;

    public const DEFAULT_RECOVERY_MAX_PASS_SECONDS = 30;

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
            recoveryBatchSize: max(1, (int) data_get($config, 'recovery.batch_size', self::DEFAULT_RECOVERY_BATCH_SIZE)),
            recoveryStaleAfterSeconds: (int) data_get($config, 'recovery.stale_after_seconds', self::EXECUTION_LOCK_SECONDS),
            maxStepAttempts: max(1, (int) data_get($config, 'limits.max_step_attempts', self::DEFAULT_MAX_STEP_ATTEMPTS)),
            maxRecords: max(1, (int) data_get($config, 'limits.max_records', self::DEFAULT_MAX_RECORDS)),
            maxDurationSeconds: max(1, (int) data_get($config, 'limits.max_duration_seconds', self::DEFAULT_MAX_DURATION_SECONDS)),
            maxCheckpointBytes: max(self::MIN_CHECKPOINT_BYTES, (int) data_get($config, 'limits.max_checkpoint_bytes', self::DEFAULT_MAX_CHECKPOINT_BYTES)),
            maxExportBytes: max(1, (int) data_get($config, 'limits.max_export_bytes', self::DEFAULT_MAX_EXPORT_BYTES)),
            maxExportCells: max(1, (int) data_get($config, 'limits.max_export_cells', self::DEFAULT_MAX_EXPORT_CELLS)),
            maxSourceRequests: max(1, (int) data_get($config, 'limits.max_source_requests', self::DEFAULT_MAX_SOURCE_REQUESTS)),
            recoveryMaxPassSeconds: (int) data_get($config, 'recovery.max_pass_seconds', self::DEFAULT_RECOVERY_MAX_PASS_SECONDS),
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
        private int $recoveryBatchSize = self::DEFAULT_RECOVERY_BATCH_SIZE,
        private int $recoveryStaleAfterSeconds = self::EXECUTION_LOCK_SECONDS,
        private int $maxStepAttempts = self::DEFAULT_MAX_STEP_ATTEMPTS,
        private int $maxRecords = self::DEFAULT_MAX_RECORDS,
        private int $maxDurationSeconds = self::DEFAULT_MAX_DURATION_SECONDS,
        private int $maxCheckpointBytes = self::DEFAULT_MAX_CHECKPOINT_BYTES,
        private int $maxExportBytes = self::DEFAULT_MAX_EXPORT_BYTES,
        private int $maxExportCells = self::DEFAULT_MAX_EXPORT_CELLS,
        private int $maxSourceRequests = self::DEFAULT_MAX_SOURCE_REQUESTS,
        private int $recoveryMaxPassSeconds = self::DEFAULT_RECOVERY_MAX_PASS_SECONDS,
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
        return max(self::EXECUTION_LOCK_SECONDS, $this->recoveryStaleAfterSeconds, $this->stepDelaySeconds + self::RECOVERY_STEP_GRACE_SECONDS);
    }

    public function recoveryMaxPassSeconds(): int
    {
        return max(1, min(self::MAX_RECOVERY_PASS_SECONDS, $this->recoveryMaxPassSeconds));
    }

    public function recoveryMutexMinutes(): int
    {
        // Admission time plus grace based on the execution lease and a two-minute margin.
        // The lease is not an I/O timeout for an already-admitted candidate.
        return (int) ceil(($this->recoveryMaxPassSeconds() + self::EXECUTION_LOCK_SECONDS) / 60) + self::RECOVERY_MUTEX_MARGIN_MINUTES;
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
