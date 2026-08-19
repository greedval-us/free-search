<?php

namespace App\Modules\Bluesky\Parser;

use App\Models\ParserRun;
use App\Modules\Bluesky\DTO\Request\BlueskyParserStartDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyParserRunStatusDTO;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserApplicationServiceInterface;
use App\Modules\ParserSupport\Contracts\ParserRunBackgroundProcessorInterface;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\ParserSupport\ParserRunHistoryRepository;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;

final class BlueskyParserApplicationService implements BlueskyParserApplicationServiceInterface, ParserRunBackgroundProcessorInterface
{
    public const MODULE_KEY = 'bluesky';

    public const DOWNLOAD_EXCEL_ROUTE = 'bluesky.parser.download-excel';

    public const DOWNLOAD_JSON_ROUTE = 'bluesky.parser.download-json';

    public function __construct(
        private readonly BlueskyParserRunStore $runStore,
        private readonly BlueskyParserCollector $collector,
        private readonly BlueskyParserRunGuard $runGuard,
        private readonly ParserRunExecutionCoordinator $executionCoordinator,
        private readonly ParserRunStatusPayloadBuilder $statusPayloadBuilder,
        private readonly ParserRunHistoryRepository $historyRepository,
        private readonly BlueskyParserHistoryPresenter $historyPresenter,
    ) {}

    public function start(BlueskyParserStartDTO $input): BlueskyParserRunStatusDTO
    {
        $run = $this->executionCoordinator->start(
            $this->runStore,
            self::MODULE_KEY,
            $input->userId,
            $input->toContext(),
        );

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?BlueskyParserRunStatusDTO
    {
        $run = $this->executionCoordinator->status(
            $this->runStore,
            $userId,
            $runId,
            fn (array $state): array => $this->collector->advance($state),
        );

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function stop(int $userId, string $runId): ?BlueskyParserRunStatusDTO
    {
        $run = $this->executionCoordinator->stop(
            $this->runStore,
            $userId,
            $runId,
            fn (array $state): array => $this->collector->buildResultSnapshot($state),
        );

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function moduleKey(): string
    {
        return self::MODULE_KEY;
    }

    public function advanceRun(int $userId, string $runId): bool
    {
        $run = $this->executionCoordinator->advance(
            $this->runStore,
            $userId,
            $runId,
            fn (array $state): array => $this->collector->advance($state),
        );

        return $this->executionCoordinator->shouldContinue($run);
    }

    public function failRun(int $userId, string $runId, string $message): void
    {
        $this->executionCoordinator->fail($this->runStore, $userId, $runId, $message);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $userId): array
    {
        return $this->historyRepository
            ->forUser($userId, self::MODULE_KEY)
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
    public function getDownloadPayload(int $userId, string $runId): array
    {
        $run = $this->runGuard->requireExistingRun($this->runStore->get($userId, $runId));

        return $this->runGuard->requireDownloadablePayload($run);
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function presentRun(array $run): BlueskyParserRunStatusDTO
    {
        return new BlueskyParserRunStatusDTO(
            $this->statusPayloadBuilder->build(
                run: $run,
                statsMap: [
                    'processedPosts' => 'processedPosts',
                    'processedAuthoredReplies' => 'processedAuthoredReplies',
                    'processedReceivedReplies' => 'processedReceivedReplies',
                    'processedFollowers' => 'processedFollowers',
                    'processedFollows' => 'processedFollows',
                    'processedReactions' => 'processedReactions',
                ],
                excelRoute: self::DOWNLOAD_EXCEL_ROUTE,
                jsonRoute: self::DOWNLOAD_JSON_ROUTE,
            )
        );
    }
}
