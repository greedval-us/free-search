<?php

namespace App\Modules\ParserSupport;

use App\Jobs\ProcessParserRun;
use App\Modules\ParserSupport\Enums\ParserRunStatus;

class ParserRunStateMachine
{
    /**
     * @param  array<string, mixed>  $state
     * @param  callable(array<string, mixed>): array<string, mixed>  $advance
     * @return array<string, mixed>
     */
    public function advance(
        array $state,
        callable $advance,
        int $nowTimestamp,
        int $advanceDelaySeconds = 2,
        bool $retryExceptions = false,
        ?callable $snapshotBuilder = null,
        string $failureMessage = 'Parser request failed.',
    ): array {
        if (($state['status'] ?? null) !== ParserRunStatus::Running->value) {
            return $state;
        }

        $cursor = is_array($state['cursor'] ?? null) ? $state['cursor'] : [];
        $nextAdvanceAt = (int) ($cursor['nextAdvanceAt'] ?? 0);

        if ($nextAdvanceAt > $nowTimestamp) {
            return $state;
        }

        if ((int) ($cursor['stepRetryUntil'] ?? PHP_INT_MAX) <= $nowTimestamp) {
            $state['status'] = ParserRunStatus::Failed->value;
            $state['stage'] = ParserRunStatus::Failed->value;
            $state['error'] = $failureMessage;
            if ($snapshotBuilder !== null) {
                $state['result'] = $snapshotBuilder($state);
            }

            return $state;
        }

        $before = $state;
        try {
            $state = $advance($state);
        } catch (\Throwable $exception) {
            if ($retryExceptions) {
                throw $exception;
            }

            $state['status'] = ParserRunStatus::Failed->value;
            $state['stage'] = ParserRunStatus::Failed->value;
            $state['error'] = $failureMessage;
            if ($snapshotBuilder !== null) {
                $state['result'] = $snapshotBuilder($state);
            }

            return $state;
        }

        if (($state['status'] ?? null) === ParserRunStatus::Failed->value && $snapshotBuilder !== null) {
            $state['result'] = $snapshotBuilder($state);
        }

        if (($state['status'] ?? null) === ParserRunStatus::Running->value) {
            $cursor = is_array($state['cursor'] ?? null) ? $state['cursor'] : [];
            $cursor['nextAdvanceAt'] = $nowTimestamp + max(0, $advanceDelaySeconds);
            if ($before !== $state) {
                $cursor['stepRetryUntil'] = $nowTimestamp + ProcessParserRun::RETRY_WINDOW_SECONDS;
                $cursor['checkpointVersion'] = (int) ($before['cursor']['checkpointVersion'] ?? 0) + 1;
            }
            $state['cursor'] = $cursor;
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  callable(array<string, mixed>): array<string, mixed>  $snapshotBuilder
     * @return array<string, mixed>
     */
    public function stop(array $state, callable $snapshotBuilder): array
    {
        if (($state['status'] ?? null) !== ParserRunStatus::Running->value) {
            return $state;
        }

        if (! is_array($state['result'] ?? null)) {
            $state['result'] = $snapshotBuilder($state);
        }

        $state['status'] = ParserRunStatus::Stopped->value;
        $state['stage'] = ParserRunStatus::Stopped->value;
        $state['error'] = null;

        return $state;
    }
}
