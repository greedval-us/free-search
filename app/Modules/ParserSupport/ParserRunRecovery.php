<?php

namespace App\Modules\ParserSupport;

use App\Jobs\ProcessParserRun;
use App\Models\ParserRun;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use Carbon\CarbonImmutable;
use Illuminate\Bus\UniqueLock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

final readonly class ParserRunRecovery
{
    public function __construct(
        private ParserRunConfig $config,
        private ParserRunJobDispatcherInterface $dispatcher,
        private ParserRunBackgroundProcessorRegistry $processors,
        private ParserRunLifecycleManager $lifecycle,
    ) {}

    /** @param callable(array<string, mixed>): array<string, mixed>|null $snapshotBuilder */
    public function recover(JsonRunStore $store, int $userId, string $runId, ?callable $snapshotBuilder = null): string
    {
        if (! $this->config->queueEnabled()) {
            return 'queue_disabled';
        }

        $run = $store->get($userId, $runId);
        if ($run === null || ! $this->isStale($run)) {
            return $run === null ? 'checkpoint_unavailable' : 'not_stale';
        }

        $connection = (string) config('queue.default');
        if (! in_array(config("queue.connections.{$connection}.driver"), ['database', 'redis', 'beanstalkd', 'sqs'], true)) {
            throw new LogicException('Parser run recovery requires an asynchronous durable queue connection.');
        }

        $version = (int) ($run['cursor']['checkpointVersion'] ?? 0);
        $lock = Cache::lock("parser-run:advance:{$store->module()}:{$userId}:{$runId}", ProcessParserRun::EXECUTION_LOCK_SECONDS);
        if (! $lock->get()) {
            $this->log($store, $runId, $version, 'execution_busy');

            return 'execution_busy';
        }

        try {
            // The stable writer lock also coordinates dispatch with stop and cleanup.
            $outcome = $store->inspectLocked($userId, $runId, function (?array $current) use ($store, $userId, $runId, &$version): string {
                $metadata = ParserRun::query()->where('run_id', $runId)->where('user_id', $userId)->where('module', $store->module())->first();
                if ($metadata === null || $metadata->expires_at?->lte(now())) {
                    return 'retention_expired';
                }
                if ($metadata->status !== ParserRunStatus::Running->value || $current === null || ! $this->isStale($current)) {
                    return $current === null ? 'checkpoint_unavailable' : 'not_stale';
                }

                $version = (int) ($current['cursor']['checkpointVersion'] ?? 0);
                if ((int) ($current['cursor']['stepRetryUntil'] ?? PHP_INT_MAX) <= now()->timestamp) {
                    return 'retry_exhausted';
                }
                if ((int) ($current['cursor']['nextAdvanceAt'] ?? 0) > now()->timestamp) {
                    return 'step_delayed';
                }

                $key = "parser-run:recovery:{$store->module()}:{$userId}:{$runId}:{$version}";
                if (! Cache::add($key, true, $this->config->recoveryStaleAfterSeconds())) {
                    return 'cooldown';
                }

                try {
                    // A lost queue message can leave the same checkpoint's uniqueness lock behind.
                    (new UniqueLock(Cache::store()))->release(new ProcessParserRun(
                        $store->module(), $userId, $runId, $version,
                        (int) ($current['cursor']['stepRetryUntil'] ?? (time() + ProcessParserRun::RETRY_WINDOW_SECONDS)),
                    ));
                    $this->dispatcher->dispatch($store->module(), $userId, $runId);
                } catch (Throwable $exception) {
                    Cache::forget($key);
                    throw $exception;
                }

                return 'requeued';
            }, waitForLock: false);

            if ($outcome === 'retry_exhausted') {
                // Module processors build partial snapshots without collecting another page.
                if ($snapshotBuilder === null) {
                    $this->processors->forModule($store->module())->failRun($userId, $runId, __('errors.api.service_unavailable'), $version, waitForLock: false);
                } else {
                    $store->mutate($userId, $runId, function (array $state) use ($version, $snapshotBuilder): array {
                        if (($state['status'] ?? null) !== ParserRunStatus::Running->value
                            || (int) ($state['cursor']['checkpointVersion'] ?? 0) !== $version) {
                            return $state;
                        }
                        $state['result'] = $snapshotBuilder($state);

                        return $this->lifecycle->markFailed($state, __('errors.api.service_unavailable'));
                    }, waitForLock: false);
                }
                $outcome = 'failed_retry_exhausted';
            }

            $this->log($store, $runId, $version, $outcome);

            return $outcome;
        } catch (ParserRunWriterLockBusyException) {
            $this->log($store, $runId, $version, 'writer_busy');

            return 'writer_busy';
        } catch (Throwable $exception) {
            $this->log($store, $runId, $version, 'recovery_error', $exception::class);
            throw $exception;
        } finally {
            $lock->release();
        }
    }

    /** @param array<string, mixed> $run */
    private function isStale(array $run): bool
    {
        if (($run['status'] ?? null) !== ParserRunStatus::Running->value) {
            return false;
        }

        try {
            $at = CarbonImmutable::parse($run['updatedAt'] ?? $run['createdAt'] ?? now());

            return $at->addSeconds($this->config->recoveryStaleAfterSeconds())->lte(now());
        } catch (Throwable) {
            return false;
        }
    }

    private function log(JsonRunStore $store, string $runId, int $version, string $outcome, ?string $exception = null): void
    {
        if (! Cache::add("parser-run:recovery-log:{$store->module()}:{$runId}:{$outcome}", true, 60)) {
            return;
        }

        Log::info('Parser run recovery evaluated.', [
            'run_id' => $runId, 'module' => $store->module(), 'checkpoint_version' => $version,
            'reason' => 'stale_checkpoint', 'outcome' => $outcome, 'exception' => $exception,
        ]);
    }
}
