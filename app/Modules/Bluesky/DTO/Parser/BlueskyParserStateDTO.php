<?php

namespace App\Modules\Bluesky\DTO\Parser;

use App\Modules\Bluesky\Enums\BlueskyParserStage;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\Support\Contracts\ArrayPayloadable;

final class BlueskyParserStateDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed>|null $result
     */
    public function __construct(
        private readonly string $runId,
        private readonly int $userId,
        private ParserRunStatus $status,
        private BlueskyParserStage $stage,
        private int $progress,
        private ?string $error,
        private readonly string $createdAt,
        private readonly string $updatedAt,
        private array $context,
        private BlueskyParserCursorDTO $cursor,
        private BlueskyParserCollectedDataDTO $data,
        private ?array $result = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            runId: (string) ($payload['runId'] ?? ''),
            userId: (int) ($payload['userId'] ?? 0),
            status: ParserRunStatus::tryFrom((string) ($payload['status'] ?? ''))
                ?? ParserRunStatus::Running,
            stage: BlueskyParserStage::tryFrom((string) ($payload['stage'] ?? ''))
                ?? BlueskyParserStage::Profile,
            progress: max(0, (int) ($payload['progress'] ?? 0)),
            error: self::nullableString($payload['error'] ?? null),
            createdAt: (string) ($payload['createdAt'] ?? ''),
            updatedAt: (string) ($payload['updatedAt'] ?? ''),
            context: is_array($payload['context'] ?? null) ? $payload['context'] : [],
            cursor: BlueskyParserCursorDTO::fromArray(is_array($payload['cursor'] ?? null) ? $payload['cursor'] : []),
            data: BlueskyParserCollectedDataDTO::fromArray(is_array($payload['data'] ?? null) ? $payload['data'] : []),
            result: is_array($payload['result'] ?? null) ? $payload['result'] : null,
        );
    }

    public function isRunning(): bool
    {
        return $this->status === ParserRunStatus::Running;
    }

    public function actorQuery(): string
    {
        return (string) ($this->context['actor'] ?? '');
    }

    public function stage(): BlueskyParserStage
    {
        return $this->stage;
    }

    public function setStage(BlueskyParserStage $stage): void
    {
        $this->stage = $stage;
    }

    public function setProgress(int $progress): void
    {
        $this->progress = max(0, min(100, $progress));
    }

    public function cursor(): BlueskyParserCursorDTO
    {
        return $this->cursor;
    }

    public function data(): BlueskyParserCollectedDataDTO
    {
        return $this->data;
    }

    public function fail(string $message, BlueskyParserStage $stage = BlueskyParserStage::Failed): void
    {
        $this->status = ParserRunStatus::Failed;
        $this->stage = $stage;
        $this->progress = 100;
        $this->error = $message;
    }

    /**
     * @param array<string, mixed> $result
     */
    public function complete(array $result, BlueskyParserStage $stage = BlueskyParserStage::Completed): void
    {
        $this->result = $result;
        $this->status = ParserRunStatus::Completed;
        $this->stage = $stage;
        $this->progress = 100;
        $this->error = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'runId' => $this->runId,
            'userId' => $this->userId,
            'status' => $this->status->value,
            'stage' => $this->stage->value,
            'progress' => $this->progress,
            'error' => $this->error,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'context' => $this->context,
            'cursor' => $this->cursor->toArray(),
            'stats' => [
                'processedPosts' => $this->data->postsCount(),
                'processedAuthoredReplies' => $this->data->authoredRepliesCount(),
                'processedReceivedReplies' => $this->data->receivedRepliesCount(),
                'processedFollowers' => $this->data->followersCount(),
                'processedFollows' => $this->data->followsCount(),
                'processedReactions' => $this->data->reactionsCount(),
            ],
            'data' => $this->data->toArray(),
            'result' => $this->result,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
