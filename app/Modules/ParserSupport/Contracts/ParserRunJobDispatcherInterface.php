<?php

namespace App\Modules\ParserSupport\Contracts;

interface ParserRunJobDispatcherInterface
{
    public function dispatch(string $module, int $userId, string $runId, int $delaySeconds = 0): void;
}
