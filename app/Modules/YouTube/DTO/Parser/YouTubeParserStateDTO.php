<?php

namespace App\Modules\YouTube\DTO\Parser;

use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\Modules\YouTube\Enums\YouTubeParserStage;
use App\Support\Contracts\ArrayPayloadable;

final class YouTubeParserStateDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed>|null $result
     */
    public function __construct(
        private readonly string $runId,
        private readonly int $userId,
        private ParserRunStatus $status,
        private YouTubeParserStage $stage,
        private int $progress,
        private ?string $error,
        private readonly string $createdAt,
        private readonly string $updatedAt,
        private array $context,
        private YouTubeParserCursorDTO $cursor,
        private YouTubeParserCollectedDataDTO $data,
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
            stage: YouTubeParserStage::tryFrom((string) ($payload['stage'] ?? ''))
                ?? YouTubeParserStage::Comments,
            progress: max(0, (int) ($payload['progress'] ?? 0)),
            error: self::nullableString($payload['error'] ?? null),
            createdAt: (string) ($payload['createdAt'] ?? ''),
            updatedAt: (string) ($payload['updatedAt'] ?? ''),
            context: is_array($payload['context'] ?? null) ? $payload['context'] : [],
            cursor: YouTubeParserCursorDTO::fromArray(is_array($payload['cursor'] ?? null) ? $payload['cursor'] : []),
            data: YouTubeParserCollectedDataDTO::fromArray(is_array($payload['data'] ?? null) ? $payload['data'] : []),
            result: is_array($payload['result'] ?? null) ? $payload['result'] : null,
        );
    }

    public function isRunning(): bool
    {
        return $this->status === ParserRunStatus::Running;
    }

    public function videoId(): string
    {
        return (string) ($this->context['videoId'] ?? '');
    }

    public function stage(): YouTubeParserStage
    {
        return $this->stage;
    }

    public function setStage(YouTubeParserStage $stage): void
    {
        $this->stage = $stage;
    }

    public function setProgress(int $progress): void
    {
        $this->progress = max(0, min(100, $progress));
    }

    public function cursor(): YouTubeParserCursorDTO
    {
        return $this->cursor;
    }

    public function data(): YouTubeParserCollectedDataDTO
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $result
     */
    public function complete(array $result, YouTubeParserStage $stage = YouTubeParserStage::Completed): void
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
                'processedComments' => $this->data->commentsCount(),
                'processedReplies' => $this->data->repliesCount(),
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
