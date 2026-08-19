<?php

namespace App\Modules\Telegram\Parser;

use App\Models\ParserRun;
use App\Modules\ParserSupport\Contracts\ParserRunBackgroundProcessorInterface;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\ParserSupport\ParserRunHistoryRepository;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;
use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use App\Modules\Telegram\DTO\Result\TelegramParserRunStatusDTO;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;

class TelegramParserApplicationService implements ParserRunBackgroundProcessorInterface, TelegramParserApplicationServiceInterface
{
    public const MODULE_KEY = 'telegram';

    public const DOWNLOAD_EXCEL_ROUTE = 'telegram.parser.download-excel';

    public const DOWNLOAD_JSON_ROUTE = 'telegram.parser.download-json';

    public function __construct(
        private readonly TelegramParserRunStore $runStore,
        private readonly TelegramParserCollector $collector,
        private readonly TelegramParserRunGuard $runGuard,
        private readonly ParserRunExecutionCoordinator $executionCoordinator,
        private readonly ParserRunStatusPayloadBuilder $statusPayloadBuilder,
        private readonly ParserRunHistoryRepository $historyRepository,
        private readonly TelegramParserHistoryPresenter $historyPresenter,
    ) {}

    public function start(TelegramParserStartDTO $input): TelegramParserRunStatusDTO
    {
        $run = $this->executionCoordinator->start(
            $this->runStore,
            self::MODULE_KEY,
            $input->userId,
            $input->toContext(),
        );

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?TelegramParserRunStatusDTO
    {
        $run = $this->executionCoordinator->status(
            $this->runStore,
            $userId,
            $runId,
            fn (array $state): array => $this->collector->advance($state),
        );

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function stop(int $userId, string $runId): ?TelegramParserRunStatusDTO
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
    private function presentRun(array $run): TelegramParserRunStatusDTO
    {
        return new TelegramParserRunStatusDTO(
            $this->statusPayloadBuilder->build(
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
