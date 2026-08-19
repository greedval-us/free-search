<?php

namespace App\Modules\ParserSupport;

use App\Jobs\ProcessParserRun;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;

final readonly class ParserRunJobDispatcher implements ParserRunJobDispatcherInterface
{
    public function __construct(
        private ParserRunExecutionConfig $config,
    ) {}

    public function dispatch(string $module, int $userId, string $runId, int $delaySeconds = 0): void
    {
        if (! $this->config->queueEnabled()) {
            return;
        }

        ProcessParserRun::dispatch($module, $userId, $runId)
            ->onQueue($this->config->queueName())
            ->delay(max(0, $delaySeconds));
    }
}
