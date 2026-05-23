<?php

namespace App\Modules\Telegram\Parser;

use App\Modules\ParserSupport\ParserRunStateMachine;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;
use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use App\Modules\Telegram\DTO\Result\ParserRunStatusDTO;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;

class TelegramParserApplicationService implements TelegramParserApplicationServiceInterface
{
    public function __construct(
        private readonly TelegramParserRunStore $runStore,
        private readonly TelegramParserCollector $collector,
        private readonly TelegramParserRunGuard $runGuard,
        private readonly ParserRunStateMachine $stateMachine,
        private readonly ParserRunStatusPayloadBuilder $statusPayloadBuilder,
    ) {
    }

    public function start(TelegramParserStartDTO $input): ParserRunStatusDTO
    {
        $run = $this->runStore->create($input->userId, $input->toContext());

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?ParserRunStatusDTO
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

    public function stop(int $userId, string $runId): ?ParserRunStatusDTO
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
    private function presentRun(array $run): ParserRunStatusDTO
    {
        return new ParserRunStatusDTO(
            $this->statusPayloadBuilder->build(
                run: $run,
                statsMap: [
                    'processedMessages' => 'processedMessages',
                    'processedComments' => 'processedComments',
                ],
                excelRoute: 'telegram.parser.download-excel',
                jsonRoute: 'telegram.parser.download-json',
            )
        );
    }
}
