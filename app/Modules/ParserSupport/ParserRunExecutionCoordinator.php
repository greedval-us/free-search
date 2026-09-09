<?php

namespace App\Modules\ParserSupport;

use App\Exceptions\FeatureAccessDeniedException;
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
    public function start(JsonRunStore $runStore, string $module, int $userId, array $context): array
    {
        return Cache::lock($this->startLockKey($module, $userId), self::START_LOCK_SECONDS)->block(
            self::START_LOCK_WAIT_SECONDS,
            function () use ($runStore, $module, $userId, $context): array {
                $activeRun = $this->historyRepository->activeForUser($userId, $module);
                if ($activeRun !== null) {
                    $storedRun = $runStore->get($userId, $activeRun->run_id);
                    if ($this->shouldContinue($storedRun)) {
                        return $storedRun;
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
    ): ?array {
        if ($this->config->queueEnabled()) {
            return $runStore->get($userId, $runId);
        }

        return $this->advance($runStore, $userId, $runId, $advance);
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
    ): ?array {
        return $runStore->mutate(
            $userId,
            $runId,
            fn (array $state): array => $this->stateMachine->advance(
                $state,
                $advance,
                now()->timestamp,
                $this->config->stepDelaySeconds(),
            ),
        );
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

    public function fail(JsonRunStore $runStore, int $userId, string $runId, string $message): void
    {
        $runStore->mutate(
            $userId,
            $runId,
            fn (array $state): array => $this->lifecycleManager->markFailed($state, $message),
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
}
