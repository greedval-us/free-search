<?php

namespace App\Modules\ParserSupport;

use App\Exceptions\FeatureAccessDeniedException;
use App\Jobs\ProcessParserRun;
use App\Models\User;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class ParserRunExecutionCoordinator
{
    private const START_LOCK_SECONDS = 10;

    private const START_LOCK_WAIT_SECONDS = 5;

    private const EXECUTION_LOCK_SECONDS = ProcessParserRun::EXECUTION_LOCK_SECONDS;

    public function __construct(
        private ParserRunConfig $config,
        private ParserRunRecovery $recovery,
        private ParserRunResourceBudget $resourceBudget,
        private ParserRunSourceRequestBudget $sourceBudget,
        private ParserRunTelemetry $telemetry,
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
                        $this->recovery->recover($runStore, $userId, (string) $storedRun['runId'], $snapshotBuilder);

                        return $runStore->get($userId, (string) $storedRun['runId']) ?? $storedRun;
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
                        $this->featureAccess->refund($user, $decision->receipt?->id);
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
                $this->recovery->recover($runStore, $userId, $runId, $snapshotBuilder);
                $run = $runStore->get($userId, $runId);
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

            $reason = $this->resourceBudget->exhaustionReason($before);
            if ($reason !== null) {
                $this->telemetry->record('Parser run resource budget exhausted.', $runStore->module(), $runId, $checkpointVersion, $reason,
                    ['step_attempts' => (int) ($before['resources']['stepAttempts'] ?? 0)],
                );

                return $runStore->mutate($userId, $runId,
                    fn (array $current): array => $current === $before ? $this->resourceBudget->fail($current, $reason, $snapshotBuilder) : $current,
                );
            }
            if ((int) ($before['cursor']['nextAdvanceAt'] ?? 0) > now()->timestamp) {
                return $runStore->get($userId, $runId);
            }
            if ((int) ($before['cursor']['stepRetryUntil'] ?? PHP_INT_MAX) <= now()->timestamp) {
                return $runStore->mutate($userId, $runId, fn (array $current): array => $current === $before
                    ? $this->stateMachine->advance($current, static fn (array $state): array => $state, now()->timestamp, $this->config->stepDelaySeconds(), $this->config->queueEnabled(), $snapshotBuilder, __('errors.api.service_unavailable'))
                    : $current,
                );
            }

            // Charge before I/O so worker crashes and retries cannot reset the total attempt budget.
            $charged = false;
            $reserved = $runStore->mutate($userId, $runId,
                function (array $current) use ($before, &$charged): array {
                    if ($current !== $before) {
                        return $current;
                    }
                    $charged = true;

                    return $this->resourceBudget->chargeAttempt($current);
                },
            );
            if (! $charged || ! $this->shouldContinue($reserved)) {
                return $reserved;
            }
            $before = $reserved;

            // External calls must not hold the writer lock needed by stop().
            $startedAt = hrtime(true);
            $outcome = 'step_error';
            try {
                try {
                    $after = $this->sourceBudget->duringRun($runStore->module(), $userId, $runId,
                        fn (): array => $this->stateMachine->advance(
                            $before,
                            $advance,
                            now()->timestamp,
                            $this->config->stepDelaySeconds(),
                            $this->config->queueEnabled(),
                            $snapshotBuilder,
                            __('errors.api.service_unavailable'),
                        ),
                    );
                    $after = $this->resourceBudget->constrainCheckpoint($before, $after, $snapshotBuilder);
                } catch (ParserRunSourceRequestBudgetExceeded) {
                    $after = $this->resourceBudget->fail($before, 'source_requests', $snapshotBuilder);
                }
                $outcome = (string) ($after['resources']['exhausted'] ?? $after['status'] ?? 'unknown');
            } finally {
                $this->telemetry->record('Parser run step measured.', $runStore->module(), $runId, $checkpointVersion, $outcome,
                    ['duration_ms' => (int) ((hrtime(true) - $startedAt) / 1000000)],
                );
            }
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

    public function fail(JsonRunStore $runStore, int $userId, string $runId, string $message, ?callable $snapshotBuilder = null, ?int $checkpointVersion = null, bool $waitForLock = true): void
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
            $waitForLock,
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
}
