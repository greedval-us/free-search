<?php

namespace App\Modules\ParserSupport;

use App\Jobs\ProcessParserRun;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\Enums\ParserRunStatus;

final readonly class ParserRunJobDispatcher implements ParserRunJobDispatcherInterface
{
    public function __construct(
        private ParserRunConfig $config,
        private ParserRunStoreRegistry $stores,
    ) {}

    public function dispatch(string $module, int $userId, string $runId, int $delaySeconds = 0): void
    {
        if (! $this->config->queueEnabled()) {
            return;
        }

        $run = $this->stores->forModule($module)->get($userId, $runId);
        if (($run['status'] ?? null) !== ParserRunStatus::Running->value) {
            return;
        }

        ProcessParserRun::dispatch(
            $module, $userId, $runId,
            (int) ($run['cursor']['checkpointVersion'] ?? 0),
            (int) ($run['cursor']['stepRetryUntil'] ?? (time() + ProcessParserRun::RETRY_WINDOW_SECONDS)),
        )
            ->onQueue($this->config->queueName())
            ->delay(max(0, $delaySeconds));
    }
}
