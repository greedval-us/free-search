<?php

namespace App\Modules\ParserSupport;

use App\Jobs\ProcessParserRun;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use Illuminate\Bus\UniqueLock;
use Illuminate\Support\Facades\Cache;

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

        $this->enqueue(ProcessParserRun::fromCheckpoint($module, $userId, $runId, $run), $delaySeconds);
    }

    public function redispatchCheckpoint(string $module, int $userId, string $runId, array $checkpoint): void
    {
        $job = ProcessParserRun::fromCheckpoint($module, $userId, $runId, $checkpoint);
        // A lost message may still hold this specific checkpoint's uniqueness lock.
        (new UniqueLock(Cache::store()))->release($job);
        $this->enqueue($job, 0);
    }

    private function enqueue(ProcessParserRun $job, int $delaySeconds): void
    {
        dispatch($job)
            ->onQueue($this->config->queueName())
            ->delay(max(0, $delaySeconds));
    }
}
