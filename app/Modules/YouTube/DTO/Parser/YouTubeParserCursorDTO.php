<?php

namespace App\Modules\YouTube\DTO\Parser;

use App\Support\Contracts\ArrayPayloadable;

final class YouTubeParserCursorDTO implements ArrayPayloadable
{
    /**
     * @param array<int, string> $replyThreadIds
     */
    public function __construct(
        private ?string $commentsPageToken = null,
        private int $commentsPage = 0,
        private int $commentsTotalHint = 0,
        private array $replyThreadIds = [],
        private int $replyThreadIndex = 0,
        private ?string $replyPageToken = null,
        private int $nextAdvanceAt = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            commentsPageToken: self::nullableString($payload['commentsPageToken'] ?? null),
            commentsPage: max(0, (int) ($payload['commentsPage'] ?? 0)),
            commentsTotalHint: max(0, (int) ($payload['commentsTotalHint'] ?? 0)),
            replyThreadIds: array_values(array_filter(
                is_array($payload['replyThreadIds'] ?? null) ? $payload['replyThreadIds'] : [],
                static fn (mixed $value): bool => trim((string) $value) !== '',
            )),
            replyThreadIndex: max(0, (int) ($payload['replyThreadIndex'] ?? 0)),
            replyPageToken: self::nullableString($payload['replyPageToken'] ?? null),
            nextAdvanceAt: max(0, (int) ($payload['nextAdvanceAt'] ?? 0)),
        );
    }

    public function commentsPageToken(): ?string
    {
        return $this->commentsPageToken;
    }

    public function setCommentsPageToken(?string $token): void
    {
        $this->commentsPageToken = self::nullableString($token);
    }

    public function incrementCommentsPage(): int
    {
        return ++$this->commentsPage;
    }

    public function commentsPage(): int
    {
        return $this->commentsPage;
    }

    public function commentsTotalHint(): int
    {
        return $this->commentsTotalHint;
    }

    public function setCommentsTotalHint(int $value): void
    {
        $this->commentsTotalHint = max(0, $value);
    }

    /**
     * @return array<int, string>
     */
    public function replyThreadIds(): array
    {
        return $this->replyThreadIds;
    }

    /**
     * @param array<int, string> $ids
     */
    public function setReplyThreadIds(array $ids): void
    {
        $normalized = [];

        foreach ($ids as $id) {
            $value = trim((string) $id);

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        $this->replyThreadIds = array_values(array_unique($normalized));
    }

    public function addReplyThreadId(string $threadId): void
    {
        $threadId = trim($threadId);

        if ($threadId === '' || in_array($threadId, $this->replyThreadIds, true)) {
            return;
        }

        $this->replyThreadIds[] = $threadId;
    }

    public function replyThreadIndex(): int
    {
        return $this->replyThreadIndex;
    }

    public function setReplyThreadIndex(int $index): void
    {
        $this->replyThreadIndex = max(0, $index);
    }

    public function incrementReplyThreadIndex(): int
    {
        return ++$this->replyThreadIndex;
    }

    public function replyPageToken(): ?string
    {
        return $this->replyPageToken;
    }

    public function setReplyPageToken(?string $token): void
    {
        $this->replyPageToken = self::nullableString($token);
    }

    public function nextAdvanceAt(): int
    {
        return $this->nextAdvanceAt;
    }

    public function setNextAdvanceAt(int $timestamp): void
    {
        $this->nextAdvanceAt = max(0, $timestamp);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'commentsPageToken' => $this->commentsPageToken ?? '',
            'commentsPage' => $this->commentsPage,
            'commentsTotalHint' => $this->commentsTotalHint,
            'replyThreadIds' => $this->replyThreadIds,
            'replyThreadIndex' => $this->replyThreadIndex,
            'replyPageToken' => $this->replyPageToken ?? '',
            'nextAdvanceAt' => $this->nextAdvanceAt,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
