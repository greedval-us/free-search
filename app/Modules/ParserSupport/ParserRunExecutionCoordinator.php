<?php

namespace App\Modules\ParserSupport;

use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use Illuminate\Support\Facades\Cache;

final readonly class ParserRunExecutionCoordinator
{
    public function __construct(
        private ParserRunExecutionConfig $config,
        private ParserRunJobDispatcherInterface $jobDispatcher,
        private ParserRunStateMachine $stateMachine,
        private ParserRunLifecycleManager $lifecycleManager,
        private ParserRunHistoryRepository $historyRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function start(JsonRunStore $runStore, string $module, int $userId, array $context): array
    {
        return Cache::lock($this->startLockKey($module, $userId), 10)->block(
            5,
            function () use ($runStore, $module, $userId, $context): array {
                $activeRun = $this->historyRepository->activeForUser($userId, $module);
                if ($activeRun !== null) {
                    $storedRun = $runStore->get($userId, $activeRun->run_id);
                    if (is_array($storedRun)) {
                        return $storedRun;
                    }
                }

                $run = $runStore->create($userId, $context);

                $this->jobDispatcher->dispatch($module, $userId, (string) $run['runId']);

                return $run;
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
