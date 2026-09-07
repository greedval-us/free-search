<?php

namespace App\Modules\Telegram\Parser;

use App\Modules\ParserSupport\AbstractParserApplicationService;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\ParserSupport\ParserRunGuard;
use App\Modules\ParserSupport\ParserRunHistoryRepository;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;
use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use App\Modules\Telegram\DTO\Result\TelegramParserRunStatusDTO;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;

class TelegramParserApplicationService extends AbstractParserApplicationService implements TelegramParserApplicationServiceInterface
{
    public const MODULE_KEY = 'telegram';

    public const DOWNLOAD_EXCEL_ROUTE = 'telegram.parser.download-excel';

    public const DOWNLOAD_JSON_ROUTE = 'telegram.parser.download-json';

    public function __construct(
        TelegramParserRunStore $runStore,
        TelegramParserCollector $collector,
        ParserRunGuard $runGuard,
        ParserRunExecutionCoordinator $executionCoordinator,
        ParserRunStatusPayloadBuilder $statusPayloadBuilder,
        ParserRunHistoryRepository $historyRepository,
        TelegramParserHistoryPresenter $historyPresenter,
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

    public function start(TelegramParserStartDTO $input): TelegramParserRunStatusDTO
    {
        $run = $this->startRun($input->userId, $input->toContext());

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?TelegramParserRunStatusDTO
    {
        $run = $this->statusRun($userId, $runId);

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function stop(int $userId, string $runId): ?TelegramParserRunStatusDTO
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
    private function presentRun(array $run): TelegramParserRunStatusDTO
    {
        return new TelegramParserRunStatusDTO(
            $this->buildStatusPayload(
                run: $run,
                statsMap: [
                    'processedMessages' => 'processedMessages',
                    'processedComments' => 'processedComments',
                ],
                excelRoute: self::DOWNLOAD_EXCEL_ROUTE,
                jsonRoute: self::DOWNLOAD_JSON_ROUTE,
            )
        );
    }
}
