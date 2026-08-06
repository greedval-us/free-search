<?php

namespace App\Modules\YouTube\Parser;

use App\Models\ParserRun;
use App\Modules\ParserSupport\ParserRunStateMachine;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;
use App\Modules\YouTube\Actions\Request\VideoCommentsAction;
use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use App\Modules\YouTube\DTO\Request\YouTubeParserStartDTO;
use App\Modules\YouTube\DTO\Result\YouTubeCommentsResultDTO;
use App\Modules\YouTube\DTO\Result\YouTubeParserRunStatusDTO;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;

class YouTubeParserApplicationService implements YouTubeParserApplicationServiceInterface
{
    public const MODULE_KEY = 'youtube';

    public const DOWNLOAD_EXCEL_ROUTE = 'youtube.parser.download-excel';

    public const DOWNLOAD_JSON_ROUTE = 'youtube.parser.download-json';

    public function __construct(
        private readonly VideoCommentsAction $videoCommentsAction,
        private readonly YouTubeParserRunStore $runStore,
        private readonly YouTubeParserCollector $collector,
        private readonly YouTubeParserRunGuard $runGuard,
        private readonly ParserRunStateMachine $stateMachine,
        private readonly ParserRunStatusPayloadBuilder $statusPayloadBuilder,
        private readonly YouTubeParserHistoryRepository $historyRepository,
        private readonly YouTubeParserHistoryPresenter $historyPresenter,
    ) {
    }

    public function comments(YouTubeCommentsQueryDTO $query): YouTubeCommentsResultDTO
    {
        return $this->videoCommentsAction->handle($query);
    }

    public function start(YouTubeParserStartDTO $input): YouTubeParserRunStatusDTO
    {
        $run = $this->runStore->create($input->userId, $input->toContext());

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?YouTubeParserRunStatusDTO
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

    public function stop(int $userId, string $runId): ?YouTubeParserRunStatusDTO
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
    private function presentRun(array $run): YouTubeParserRunStatusDTO
    {
        return new YouTubeParserRunStatusDTO(
            $this->statusPayloadBuilder->build(
                run: $run,
                statsMap: [
                    'processedComments' => 'processedComments',
                    'processedReplies' => 'processedReplies',
                ],
                excelRoute: self::DOWNLOAD_EXCEL_ROUTE,
                jsonRoute: self::DOWNLOAD_JSON_ROUTE,
            )
        );
    }
}
