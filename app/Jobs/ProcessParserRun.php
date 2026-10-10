<?php

namespace App\Jobs;

use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunBackgroundProcessorRegistry;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunOverlapMiddleware;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class ProcessParserRun implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    public const TIMEOUT_SECONDS = 120;

    public const EXECUTION_LOCK_SECONDS = self::TIMEOUT_SECONDS + 30;

    private const MAX_EXCEPTIONS = 3;

    public const RETRY_WINDOW_SECONDS = 3600;

    private const UNIQUE_FOR_SECONDS = 3600;

    private const OVERLAP_RELEASE_SECONDS = 3;

    private const OVERLAP_EXPIRY_GRACE_SECONDS = 30;

    private const SERIALIZED_MODULES = ['telegram'];

    use Queueable;

    // Lock releases are not failures. Bound retries by time and real exceptions instead.
    public int $tries = 0;

    public int $maxExceptions = self::MAX_EXCEPTIONS;

    public array $backoff = [5, 15, 60];

    public ?int $checkpointVersion = null;

    public ?int $queuedAt = null;

    public int $retryDeadline;

    public int $timeout = self::TIMEOUT_SECONDS;

    public bool $failOnTimeout = true;

    public int $uniqueFor = self::UNIQUE_FOR_SECONDS;

    public function __construct(
        public readonly string $module,
        public readonly int $userId,
        public readonly string $runId,
        ?int $checkpointVersion = null,
        ?int $retryDeadline = null,
    ) {
        $this->checkpointVersion = $checkpointVersion;
        $this->queuedAt = now()->timestamp;
        $this->retryDeadline = $retryDeadline ?? (time() + self::RETRY_WINDOW_SECONDS);
    }

    public function retryUntil(): int
    {
        return $this->retryDeadline;
    }

    public function handle(
        ParserRunBackgroundProcessorRegistry $registry,
        ParserRunJobDispatcherInterface $jobDispatcher,
        ParserRunConfig $config,
    ): void {
        $shouldContinue = $registry
            ->forModule($this->module)
            ->advanceRun($this->userId, $this->runId, $this->checkpointVersion);

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
            ->failRun($this->userId, $this->runId, $exception->getMessage(), $this->checkpointVersion);
    }

    public function uniqueId(): string
    {
        return implode(':', [$this->module, $this->userId, $this->runId, $this->checkpointVersion ?? 'legacy']);
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
            (new ParserRunOverlapMiddleware("parser-run:module:{$this->module}"))
                ->releaseAfter(self::OVERLAP_RELEASE_SECONDS)
                ->expireAfter($this->timeout + self::OVERLAP_EXPIRY_GRACE_SECONDS),
        ];
    }
}
