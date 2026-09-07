<?php

namespace App\Modules\Mastodon\Parser;

use App\Modules\Mastodon\DTO\Request\MastodonParserStartDTO;
use App\Modules\Mastodon\DTO\Result\MastodonParserRunStatusDTO;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserApplicationServiceInterface;
use App\Modules\ParserSupport\AbstractParserApplicationService;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\ParserSupport\ParserRunGuard;
use App\Modules\ParserSupport\ParserRunHistoryRepository;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;

final class MastodonParserApplicationService extends AbstractParserApplicationService implements MastodonParserApplicationServiceInterface
{
    public const MODULE_KEY = 'mastodon';

    public const DOWNLOAD_EXCEL_ROUTE = 'mastodon.parser.download-excel';

    public const DOWNLOAD_JSON_ROUTE = 'mastodon.parser.download-json';

    public function __construct(
        MastodonParserRunStore $runStore,
        MastodonParserCollector $collector,
        ParserRunGuard $runGuard,
        ParserRunExecutionCoordinator $executionCoordinator,
        ParserRunStatusPayloadBuilder $statusPayloadBuilder,
        ParserRunHistoryRepository $historyRepository,
        MastodonParserHistoryPresenter $historyPresenter,
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

    public function start(MastodonParserStartDTO $input): MastodonParserRunStatusDTO
    {
        $run = $this->startRun($input->userId, $input->toContext());

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?MastodonParserRunStatusDTO
    {
        $run = $this->statusRun($userId, $runId);

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function stop(int $userId, string $runId): ?MastodonParserRunStatusDTO
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
    private function presentRun(array $run): MastodonParserRunStatusDTO
    {
        return new MastodonParserRunStatusDTO(
            $this->buildStatusPayload(
                run: $run,
                statsMap: [
                    'processedStatuses' => 'processedStatuses',
                    'processedComments' => 'processedComments',
                ],
                excelRoute: self::DOWNLOAD_EXCEL_ROUTE,
                jsonRoute: self::DOWNLOAD_JSON_ROUTE,
            )
        );
    }
}
