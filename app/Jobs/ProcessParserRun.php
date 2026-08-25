<?php

namespace App\Jobs;

use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunBackgroundProcessorRegistry;
use App\Modules\ParserSupport\ParserRunExecutionConfig;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

final class ProcessParserRun implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    public const TIMEOUT_SECONDS = 120;

    private const SERIALIZED_MODULES = ['telegram'];

    use Queueable;

    public int $tries = 3;

    public int $timeout = self::TIMEOUT_SECONDS;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly string $module,
        public readonly int $userId,
        public readonly string $runId,
    ) {}

    public function handle(
        ParserRunBackgroundProcessorRegistry $registry,
        ParserRunJobDispatcherInterface $jobDispatcher,
        ParserRunExecutionConfig $config,
    ): void {
        $shouldContinue = $registry
            ->forModule($this->module)
            ->advanceRun($this->userId, $this->runId);

        if ($shouldContinue) {
            $jobDispatcher->dispatch(
                $this->module,
                $this->userId,
                $this->runId,
                $config->stepDelaySeconds(),
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception === null) {
            return;
        }

        app(ParserRunBackgroundProcessorRegistry::class)
            ->forModule($this->module)
            ->failRun($this->userId, $this->runId, $exception->getMessage());
    }

    public function uniqueId(): string
    {
        return implode(':', [$this->module, $this->userId, $this->runId]);
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        if (! in_array($this->module, self::SERIALIZED_MODULES, true)) {
            return [];
        }

        return [
            (new WithoutOverlapping("parser-run:module:{$this->module}"))
                ->releaseAfter(3)
                ->expireAfter($this->timeout + 30),
        ];
    }
}
