<?php

namespace App\Modules\Bluesky\Parser;

use App\Modules\Bluesky\DTO\Request\BlueskyParserStartDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyParserRunStatusDTO;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserApplicationServiceInterface;
use App\Modules\ParserSupport\AbstractParserApplicationService;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\ParserSupport\ParserRunGuard;
use App\Modules\ParserSupport\ParserRunHistoryRepository;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;

final class BlueskyParserApplicationService extends AbstractParserApplicationService implements BlueskyParserApplicationServiceInterface
{
    public const MODULE_KEY = 'bluesky';

    public const DOWNLOAD_EXCEL_ROUTE = 'bluesky.parser.download-excel';

    public const DOWNLOAD_JSON_ROUTE = 'bluesky.parser.download-json';

    public function __construct(
        BlueskyParserRunStore $runStore,
        BlueskyParserCollector $collector,
        ParserRunGuard $runGuard,
        ParserRunExecutionCoordinator $executionCoordinator,
        ParserRunStatusPayloadBuilder $statusPayloadBuilder,
        ParserRunHistoryRepository $historyRepository,
        BlueskyParserHistoryPresenter $historyPresenter,
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

    public function start(BlueskyParserStartDTO $input): BlueskyParserRunStatusDTO
    {
        $run = $this->startRun($input->userId, $input->toContext());

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?BlueskyParserRunStatusDTO
    {
        $run = $this->statusRun($userId, $runId);

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function stop(int $userId, string $runId): ?BlueskyParserRunStatusDTO
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
    private function presentRun(array $run): BlueskyParserRunStatusDTO
    {
        return new BlueskyParserRunStatusDTO(
            $this->buildStatusPayload(
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
