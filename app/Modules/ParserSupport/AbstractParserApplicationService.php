<?php

namespace App\Modules\ParserSupport;

use App\Models\ParserRun;
use App\Modules\ParserSupport\Contracts\ParserRunApplicationServiceInterface;
use App\Modules\ParserSupport\Contracts\ParserRunBackgroundProcessorInterface;
use App\Modules\ParserSupport\Contracts\ParserRunCollectorInterface;
use App\Modules\ParserSupport\Contracts\ParserRunHistoryPresenterInterface;

abstract class AbstractParserApplicationService implements ParserRunApplicationServiceInterface, ParserRunBackgroundProcessorInterface
{
    public function __construct(
        private readonly JsonRunStore $runStore,
        private readonly ParserRunCollectorInterface $collector,
        private readonly ParserRunGuard $runGuard,
        private readonly ParserRunExecutionCoordinator $executionCoordinator,
        private readonly ParserRunStatusPayloadBuilder $statusPayloadBuilder,
        private readonly ParserRunHistoryRepository $historyRepository,
        private readonly ParserRunHistoryPresenterInterface $historyPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    final protected function startRun(int $userId, array $context): array
    {
        return $this->executionCoordinator->start(
            $this->runStore,
            $this->moduleKey(),
            $userId,
            $context,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    final protected function statusRun(int $userId, string $runId): ?array
    {
        return $this->executionCoordinator->status(
            $this->runStore,
            $userId,
            $runId,
            $this->collector->advance(...),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    final protected function stopRun(int $userId, string $runId): ?array
    {
        return $this->executionCoordinator->stop(
            $this->runStore,
            $userId,
            $runId,
            $this->collector->buildResultSnapshot(...),
        );
    }

    final public function advanceRun(int $userId, string $runId): bool
    {
        $run = $this->executionCoordinator->advance(
            $this->runStore,
            $userId,
            $runId,
            $this->collector->advance(...),
        );

        return $this->executionCoordinator->shouldContinue($run);
    }

    final public function failRun(int $userId, string $runId, string $message): void
    {
        $this->executionCoordinator->fail($this->runStore, $userId, $runId, $message);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    final public function history(int $userId): array
    {
        return $this->historyRepository
            ->forUser($userId, $this->moduleKey())
            ->map(function (ParserRun $metadata) use ($userId): array {
                $run = $this->runStore->get($userId, $metadata->run_id);

                return $this->historyPresenter->present($metadata, $run);
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    final public function getDownloadPayload(int $userId, string $runId): array
    {
        $run = $this->runGuard->requireExistingRun($this->runStore->get($userId, $runId));

        return $this->runGuard->requireDownloadablePayload($run);
    }

    /**
     * @param  array<string, mixed>  $run
     * @param  array<string, string>  $statsMap
     * @return array<string, mixed>
     */
    final protected function buildStatusPayload(
        array $run,
        array $statsMap,
        string $excelRoute,
        string $jsonRoute,
    ): array {
        return $this->statusPayloadBuilder->build($run, $statsMap, $excelRoute, $jsonRoute);
    }
}
