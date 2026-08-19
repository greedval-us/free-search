<?php

namespace App\Modules\ParserSupport;

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
    ): array {
        if (($state['status'] ?? null) !== ParserRunStatus::Running->value) {
            return $state;
        }

        $cursor = is_array($state['cursor'] ?? null) ? $state['cursor'] : [];
        $nextAdvanceAt = (int) ($cursor['nextAdvanceAt'] ?? 0);

        if ($nextAdvanceAt > $nowTimestamp) {
            return $state;
        }

        try {
            $state = $advance($state);
        } catch (\Throwable $exception) {
            $state['status'] = ParserRunStatus::Failed->value;
            $state['stage'] = 'failed';
            $state['progress'] = 100;
            $state['error'] = $exception->getMessage();

            return $state;
        }

        if (($state['status'] ?? null) === ParserRunStatus::Running->value) {
            $cursor = is_array($state['cursor'] ?? null) ? $state['cursor'] : [];
            $cursor['nextAdvanceAt'] = $nowTimestamp + max(0, $advanceDelaySeconds);
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
        $state['stage'] = 'stopped';
        $state['error'] = null;

        return $state;
    }
}
