<?php

namespace App\Modules\Bluesky\Parser;

use App\Modules\Bluesky\DTO\Request\BlueskyParserStartDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyParserRunStatusDTO;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserApplicationServiceInterface;
use App\Modules\ParserSupport\ParserRunStateMachine;
use App\Modules\ParserSupport\ParserRunStatusPayloadBuilder;

final class BlueskyParserApplicationService implements BlueskyParserApplicationServiceInterface
{
    public function __construct(
        private readonly BlueskyParserRunStore $runStore,
        private readonly BlueskyParserCollector $collector,
        private readonly BlueskyParserRunGuard $runGuard,
        private readonly ParserRunStateMachine $stateMachine,
        private readonly ParserRunStatusPayloadBuilder $statusPayloadBuilder,
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
                excelRoute: 'bluesky.parser.download-excel',
                jsonRoute: 'bluesky.parser.download-json',
            )
        );
    }
}
