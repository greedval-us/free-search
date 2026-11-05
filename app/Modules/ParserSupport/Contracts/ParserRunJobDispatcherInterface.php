<?php

namespace App\Modules\ParserSupport\Contracts;

interface ParserRunJobDispatcherInterface
{
    public function dispatch(string $module, int $userId, string $runId, int $delaySeconds = 0): void;

    /** @param array<string, mixed> $checkpoint The running checkpoint held under the recovery writer lock. */
    public function redispatchCheckpoint(string $module, int $userId, string $runId, array $checkpoint): void;
}
