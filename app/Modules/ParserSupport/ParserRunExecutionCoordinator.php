<?php

namespace App\Modules\ParserSupport;

use App\Exceptions\FeatureAccessDeniedException;
use App\Jobs\ProcessParserRun;
use App\Models\User;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Bus\UniqueLock;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class ParserRunExecutionCoordinator
{
    private const START_LOCK_SECONDS = 10;

    private const START_LOCK_WAIT_SECONDS = 5;

    private const EXECUTION_LOCK_SECONDS = ProcessParserRun::TIMEOUT_SECONDS + 30;

    public function __construct(
        private ParserRunConfig $config,
        private ParserRunJobDispatcherInterface $jobDispatcher,
        private ParserRunStateMachine $stateMachine,
        private ParserRunLifecycleManager $lifecycleManager,
        private ParserRunHistoryRepository $historyRepository,
        private FeatureAccessServiceInterface $featureAccess,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function start(JsonRunStore $runStore, string $module, int $userId, array $context, ?callable $snapshotBuilder = null): array
    {
        return Cache::lock($this->startLockKey($module, $userId), self::START_LOCK_SECONDS)->block(
            self::START_LOCK_WAIT_SECONDS,
            function () use ($runStore, $module, $userId, $context, $snapshotBuilder): array {
                $activeRun = $this->historyRepository->activeForUser($userId, $module);
                if ($activeRun !== null) {
                    $storedRun = $runStore->get($userId, $activeRun->run_id);
                    if ($this->shouldContinue($storedRun)) {
                        return $this->recoverStaleRun($runStore, $userId, $storedRun, $snapshotBuilder);
                    }
                }

                $user = User::query()->findOrFail($userId);
                $resource = $module.'.parser';
                $decision = $this->featureAccess->consumeResource($user, $resource);
                if (! $decision->allowed) {
                    throw new FeatureAccessDeniedException($decision);
                }

                try {
                    $run = $runStore->create($userId, $context);
                    $this->jobDispatcher->dispatch($module, $userId, (string) $run['runId']);

                    return $run;
                } catch (Throwable $exception) {
                    try {
                        if (isset($run)) {
                            $this->fail($runStore, $userId, (string) $run['runId'], __('errors.api.service_unavailable'));
                        }
                    } finally {
                        $this->featureAccess->refundResource($user, $resource);
                    }

                    throw $exception;
                }
            },
        );
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $advance
     * @return array<string, mixed>|null
     */
    public function status(
        JsonRunStore $runStore,
        int $userId,
        string $runId,
        callable $advance,
        ?callable $snapshotBuilder = null,
    ): ?array {
        if ($this->config->queueEnabled()) {
            $run = $runStore->get($userId, $runId);
            if ($run !== null) {
                $run = $this->recoverStaleRun($runStore, $userId, $run, $snapshotBuilder);
            }

            return $run;
        }

        return $this->advance($runStore, $userId, $runId, $advance, $snapshotBuilder);
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $advance
     * @return array<string, mixed>|null
     */
    public function advance(
        JsonRunStore $runStore,
        int $userId,
        string $runId,
        callable $advance,
        ?callable $snapshotBuilder = null,
        ?int $checkpointVersion = null,
    ): ?array {
        $lock = Cache::lock($this->executionLockKey($runStore, $userId, $runId), self::EXECUTION_LOCK_SECONDS);
        if (! $lock->get()) {
            $current = $runStore->get($userId, $runId);
            if ($checkpointVersion !== null && $this->shouldContinue($current)
                && (int) ($current['cursor']['checkpointVersion'] ?? 0) !== $checkpointVersion) {
                return null;
            }

            return $current;
        }

        try {
            $before = $runStore->get($userId, $runId);
            if (! $this->shouldContinue($before)) {
                return $before === null ? null : $runStore->mutate($userId, $runId, static fn (array $state): array => $state);
            }
            if ($checkpointVersion !== null && (int) ($before['cursor']['checkpointVersion'] ?? 0) !== $checkpointVersion) {
                return null;
            }

            // External calls must not hold the writer lock needed by stop().
            $after = $this->stateMachine->advance(
                $before,
                $advance,
                now()->timestamp,
                $this->config->stepDelaySeconds(),
                $this->config->queueEnabled(),
                $snapshotBuilder,
                __('errors.api.service_unavailable'),
            );
            if ($before === $after) {
                return $runStore->get($userId, $runId);
            }

            return $runStore->mutate(
                $userId,
                $runId,
                // A stop or a newer checkpoint wins over this in-flight step.
                static fn (array $current): array => $current === $before ? $after : $current,
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $snapshotBuilder
     * @return array<string, mixed>|null
     */
    public function stop(
        JsonRunStore $runStore,
        int $userId,
        string $runId,
        callable $snapshotBuilder,
    ): ?array {
        return $runStore->mutate(
            $userId,
            $runId,
            fn (array $state): array => $this->stateMachine->stop($state, $snapshotBuilder),
        );
    }

    public function shouldContinue(?array $run): bool
    {
        return ($run['status'] ?? null) === ParserRunStatus::Running->value;
    }

    public function fail(JsonRunStore $runStore, int $userId, string $runId, string $message, ?callable $snapshotBuilder = null, ?int $checkpointVersion = null): void
    {
        $runStore->mutate(
            $userId,
            $runId,
            function (array $state) use ($message, $snapshotBuilder, $checkpointVersion): array {
                if (! $this->shouldContinue($state)) {
                    return $state;
                }
                if ($checkpointVersion !== null && (int) ($state['cursor']['checkpointVersion'] ?? 0) !== $checkpointVersion) {
                    return $state;
                }
                if ($snapshotBuilder !== null) {
                    $state['result'] = $snapshotBuilder($state);
                }

                return $this->lifecycleManager->markFailed($state, $message);
            },
        );
    }

    public function nextStepDelaySeconds(): int
    {
        return $this->config->stepDelaySeconds();
    }

    private function startLockKey(string $module, int $userId): string
    {
        return "parser-run:start:{$module}:{$userId}";
    }

    private function executionLockKey(JsonRunStore $runStore, int $userId, string $runId): string
    {
        return "parser-run:advance:{$runStore->module()}:{$userId}:{$runId}";
    }

    /** @param array<string, mixed> $run */
    private function recoverStaleRun(JsonRunStore $runStore, int $userId, array $run, ?callable $snapshotBuilder = null): array
    {
        if (! $this->config->queueEnabled() || ! $this->shouldContinue($run)) {
            return $run;
        }

        $staleAfter = max(self::EXECUTION_LOCK_SECONDS, $this->config->stepDelaySeconds() + 30);
        $updatedAt = CarbonImmutable::parse($run['updatedAt'] ?? $run['createdAt'] ?? now());
        if ($updatedAt->addSeconds($staleAfter)->isFuture()) {
            return $run;
        }

        $runId = (string) ($run['runId'] ?? '');
        $lock = Cache::lock($this->executionLockKey($runStore, $userId, $runId), self::EXECUTION_LOCK_SECONDS);
        if (! $lock->get()) {
            return $run;
        }

        try {
            $run = $runStore->get($userId, $runId) ?? $run;
            $updatedAt = CarbonImmutable::parse($run['updatedAt'] ?? $run['createdAt'] ?? now());
            if (! $this->shouldContinue($run) || $updatedAt->addSeconds($staleAfter)->isFuture()) {
                return $run;
            }
            // Recovery must not reset the durable retry budget for this checkpoint.
            if ((int) ($run['cursor']['stepRetryUntil'] ?? PHP_INT_MAX) <= now()->timestamp) {
                $this->fail($runStore, $userId, $runId, __('errors.api.service_unavailable'), $snapshotBuilder);

                return $runStore->get($userId, $runId) ?? $run;
            }

            $recoveryKey = 'parser-run:recovery:'.$runStore->module().':'.$userId.':'.$runId;
            if (! Cache::add($recoveryKey, true, $staleAfter)) {
                return $run;
            }

            try {
                // The queued job may have been lost with its uniqueness lock still alive.
                (new UniqueLock(Cache::store()))->release(new ProcessParserRun(
                    $runStore->module(), $userId, $runId,
                    (int) ($run['cursor']['checkpointVersion'] ?? 0),
                    (int) ($run['cursor']['stepRetryUntil'] ?? (time() + ProcessParserRun::RETRY_WINDOW_SECONDS)),
                ));
                $this->jobDispatcher->dispatch($runStore->module(), $userId, $runId);
            } catch (Throwable $exception) {
                Cache::forget($recoveryKey);
                throw $exception;
            }

            return $run;
        } finally {
            $lock->release();
        }
    }
}
