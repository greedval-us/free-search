<?php

namespace App\Modules\YouTube\Parser;

use App\Modules\ParserSupport\AbstractParserApplicationService;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\ParserSupport\ParserRunGuard;
use App\Modules\ParserSupport\ParserRunHistoryRepository;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;
use App\Modules\YouTube\Actions\Request\VideoCommentsAction;
use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use App\Modules\YouTube\DTO\Request\YouTubeParserStartDTO;
use App\Modules\YouTube\DTO\Result\YouTubeCommentsResultDTO;
use App\Modules\YouTube\DTO\Result\YouTubeParserRunStatusDTO;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;

class YouTubeParserApplicationService extends AbstractParserApplicationService implements YouTubeParserApplicationServiceInterface
{
    public const MODULE_KEY = 'youtube';

    public const DOWNLOAD_EXCEL_ROUTE = 'youtube.parser.download-excel';

    public const DOWNLOAD_JSON_ROUTE = 'youtube.parser.download-json';

    public function __construct(
        private readonly VideoCommentsAction $videoCommentsAction,
        YouTubeParserRunStore $runStore,
        YouTubeParserCollector $collector,
        ParserRunGuard $runGuard,
        ParserRunExecutionCoordinator $executionCoordinator,
        ParserRunStatusPayloadBuilder $statusPayloadBuilder,
        ParserRunHistoryRepository $historyRepository,
        YouTubeParserHistoryPresenter $historyPresenter,
    ) {
        parent::__construct(
            $runStore,
            $collector,
            $runGuard,
            $executionCoordinator,
            $statusPayloadBuilder,
            $historyRepository,
            $historyPresenter,
        );
    }

    public function comments(YouTubeCommentsQueryDTO $query): YouTubeCommentsResultDTO
    {
        return $this->videoCommentsAction->handle($query);
    }

    public function start(YouTubeParserStartDTO $input): YouTubeParserRunStatusDTO
    {
        $run = $this->startRun($input->userId, $input->toContext());

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?YouTubeParserRunStatusDTO
    {
        $run = $this->statusRun($userId, $runId);

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function stop(int $userId, string $runId): ?YouTubeParserRunStatusDTO
    {
        $run = $this->stopRun($userId, $runId);

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function moduleKey(): string
    {
        return self::MODULE_KEY;
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function presentRun(array $run): YouTubeParserRunStatusDTO
    {
        return new YouTubeParserRunStatusDTO(
            $this->buildStatusPayload(
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
