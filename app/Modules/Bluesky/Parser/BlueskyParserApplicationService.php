<?php

namespace App\Modules\Bluesky\Parser;

use App\Models\ParserRun;
use App\Modules\Bluesky\DTO\Request\BlueskyParserStartDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyParserRunStatusDTO;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserApplicationServiceInterface;
use App\Modules\ParserSupport\ParserRunStateMachine;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;

final class BlueskyParserApplicationService implements BlueskyParserApplicationServiceInterface
{
    public const MODULE_KEY = 'bluesky';

    public const DOWNLOAD_EXCEL_ROUTE = 'bluesky.parser.download-excel';

    public const DOWNLOAD_JSON_ROUTE = 'bluesky.parser.download-json';

    public function __construct(
        private readonly BlueskyParserRunStore $runStore,
        private readonly BlueskyParserCollector $collector,
        private readonly BlueskyParserRunGuard $runGuard,
        private readonly ParserRunStateMachine $stateMachine,
        private readonly ParserRunStatusPayloadBuilder $statusPayloadBuilder,
        private readonly BlueskyParserHistoryRepository $historyRepository,
        private readonly BlueskyParserHistoryPresenter $historyPresenter,
    ) {
    }

    public function start(BlueskyParserStartDTO $input): BlueskyParserRunStatusDTO
    {
        $run = $this->runStore->create($input->userId, $input->toContext());

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?BlueskyParserRunStatusDTO
    {
        $nowTs = now()->timestamp;
        $run = $this->runStore->mutate(
            $userId,
            $runId,
            fn (array $state): array => $this->stateMachine->advance(
                $state,
                fn (array $current): array => $this->collector->advance($current),
                $nowTs
            )
        );

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function stop(int $userId, string $runId): ?BlueskyParserRunStatusDTO
    {
        $run = $this->runStore->mutate(
            $userId,
            $runId,
            fn (array $state): array => $this->stateMachine->stop(
                $state,
                fn (array $current): array => $this->collector->buildResultSnapshot($current)
            )
        );

        return is_array($run) ? $this->presentRun($run) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $userId): array
    {
        return $this->historyRepository
            ->forUser($userId)
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
     * @param array<string, mixed> $run
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
