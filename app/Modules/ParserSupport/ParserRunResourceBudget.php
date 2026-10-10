<?php

namespace App\Modules\ParserSupport;

use Carbon\CarbonImmutable;
use Throwable;

final readonly class ParserRunResourceBudget
{
    public function __construct(private ParserRunConfig $config, private ParserRunLifecycleManager $lifecycle) {}

    /** @param array<string, mixed> $run */
    public function exhaustionReason(array $run): ?string
    {
        if ($this->recordsExceedLimit($run)) {
            return 'records';
        }
        if ((int) ($run['resources']['stepAttempts'] ?? 0) >= $this->config->maxStepAttempts()) {
            return 'step_attempts';
        }

        try {
            if (CarbonImmutable::parse($run['createdAt'] ?? now())->addSeconds($this->config->maxDurationSeconds())->lte(now())) {
                return 'duration';
            }
        } catch (Throwable) {
            // Legacy checkpoints without a valid start date still have attempt and byte limits.
        }

        return null;
    }

    /** @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function chargeAttempt(array $run): array
    {
        $run['resources']['stepAttempts'] = max(0, (int) ($run['resources']['stepAttempts'] ?? 0)) + 1;

        return $run;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  callable(array<string, mixed>): array<string, mixed>|null  $snapshotBuilder
     * @return array<string, mixed>
     */
    public function constrainCheckpoint(array $before, array $after, ?callable $snapshotBuilder): array
    {
        // Source DTOs serialize only their own fields; the shared budget remains durable.
        $after['resources'] = $before['resources'];
        if ($this->recordsExceedLimit($after)) {
            return $this->fail($before, 'records', $snapshotBuilder);
        }
        $preview = $after;
        if ($snapshotBuilder !== null && ! is_array($preview['result'] ?? null)) {
            $preview['result'] = $snapshotBuilder($preview);
        }

        return $this->size($preview) <= $this->config->maxCheckpointBytes()
            ? $after
            : $this->fail($before, 'checkpoint_bytes', $snapshotBuilder);
    }

    /**
     * @param  array<string, mixed>  $run
     * @param  callable(array<string, mixed>): array<string, mixed>|null  $snapshotBuilder
     * @return array<string, mixed>
     */
    public function fail(array $run, string $reason, ?callable $snapshotBuilder): array
    {
        $run['resources']['exhausted'] = $reason;
        $run = $this->lifecycle->markFailed($run, __('errors.api.parser_run.limit_'.$reason));
        if ($snapshotBuilder !== null) {
            $run['result'] = $snapshotBuilder($run);
        }
        if ($this->size($run) > $this->config->maxCheckpointBytes()) {
            // The cap admits collector data. Finalization metadata may add a small overhead;
            // preserve saved data, including legacy oversized files, without a duplicate snapshot.
            $run['result'] = null;
        }

        return $run;
    }

    /** @param array<string, mixed> $run */
    private function size(array $run): int
    {
        return strlen(json_encode($run, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @param array<string, mixed> $run */
    private function recordsExceedLimit(array $run): bool
    {
        $remaining = $this->config->maxRecords();
        foreach ((array) ($run['stats'] ?? []) as $name => $count) {
            // The shared stats contract contains cumulative collected-record counters, not page hints.
            if (! str_starts_with((string) $name, 'processed')) {
                continue;
            }
            $count = max(0, (int) $count);
            if ($count > $remaining) {
                return true;
            }
            $remaining -= $count;
        }

        return false;
    }
}
