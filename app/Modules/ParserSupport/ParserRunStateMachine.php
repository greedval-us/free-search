<?php

namespace App\Modules\ParserSupport;

class ParserRunStateMachine
{
    /**
     * @param array<string, mixed> $state
     * @param callable(array<string, mixed>): array<string, mixed> $advance
     * @return array<string, mixed>
     */
    public function advance(array $state, callable $advance, int $nowTimestamp): array
    {
        if (($state['status'] ?? null) !== 'running') {
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
            $state['status'] = 'failed';
            $state['stage'] = 'failed';
            $state['progress'] = 100;
            $state['error'] = $exception->getMessage();

            return $state;
        }

        if (($state['status'] ?? null) === 'running') {
            $cursor = is_array($state['cursor'] ?? null) ? $state['cursor'] : [];
            $cursor['nextAdvanceAt'] = $nowTimestamp + 2;
            $state['cursor'] = $cursor;
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @param callable(array<string, mixed>): array<string, mixed> $snapshotBuilder
     * @return array<string, mixed>
     */
    public function stop(array $state, callable $snapshotBuilder): array
    {
        if (($state['status'] ?? null) === 'completed') {
            return $state;
        }

        if (!is_array($state['result'] ?? null)) {
            $state['result'] = $snapshotBuilder($state);
        }

        $state['status'] = 'stopped';
        $state['stage'] = 'stopped';
        $state['error'] = null;

        return $state;
    }
}

