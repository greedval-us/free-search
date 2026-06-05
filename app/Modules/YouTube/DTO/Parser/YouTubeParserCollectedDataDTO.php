<?php

namespace App\Modules\YouTube\DTO\Parser;

use App\Support\Contracts\ArrayPayloadable;

final class YouTubeParserCollectedDataDTO implements ArrayPayloadable
{
    /**
     * @param array<string, bool> $commentIds
     * @param array<string, bool> $replyIds
     * @param array<string, string> $threadParentMap
     * @param array<int, array<string, mixed>> $commentsIndex
     * @param array<int, array<string, mixed>> $repliesIndex
     */
    public function __construct(
        private array $commentIds = [],
        private array $replyIds = [],
        private array $threadParentMap = [],
        private array $commentsIndex = [],
        private array $repliesIndex = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            commentIds: self::boolMap($payload['commentIds'] ?? []),
            replyIds: self::boolMap($payload['replyIds'] ?? []),
            threadParentMap: self::stringMap($payload['threadParentMap'] ?? []),
            commentsIndex: self::items($payload['commentsIndex'] ?? []),
            repliesIndex: self::items($payload['repliesIndex'] ?? []),
        );
    }

    public function commentsCount(): int
    {
        return count($this->commentsIndex);
    }

    public function repliesCount(): int
    {
        return count($this->repliesIndex);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function commentsIndex(): array
    {
        return $this->commentsIndex;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function repliesIndex(): array
    {
        return $this->repliesIndex;
    }

    public function threadParentId(string $threadId): string
    {
        return (string) ($this->threadParentMap[$threadId] ?? '');
    }

    /**
     * @param array<string, mixed> $presented
     */
    public function appendCommentThread(array $presented, string $videoId): bool
    {
        $commentId = (string) ($presented['id'] ?? '');
        $threadId = (string) ($presented['threadId'] ?? '');

        if ($commentId === '' || $threadId === '') {
            return false;
        }

        if (! isset($this->commentIds[$commentId])) {
            $this->commentIds[$commentId] = true;
            $this->commentsIndex[] = [
                'commentId' => $commentId,
                'threadId' => $threadId,
                'videoId' => $videoId,
                'author' => (string) ($presented['author'] ?? ''),
                'authorChannelUrl' => (string) ($presented['authorChannelUrl'] ?? ''),
                'text' => (string) ($presented['text'] ?? ''),
                'likeCount' => (int) ($presented['likeCount'] ?? 0),
                'publishedAt' => (string) ($presented['publishedAt'] ?? ''),
                'updatedAt' => (string) ($presented['updatedAt'] ?? ''),
                'replyCount' => (int) ($presented['replyCount'] ?? 0),
            ];
        }

        $this->threadParentMap[$threadId] = $commentId;

        return true;
    }

    /**
     * @param array<string, mixed> $reply
     */
    public function appendEmbeddedReply(array $reply, string $commentId, string $threadId): bool
    {
        $replyId = (string) ($reply['id'] ?? '');
        if ($replyId === '' || isset($this->replyIds[$replyId])) {
            return false;
        }

        $this->replyIds[$replyId] = true;
        $this->repliesIndex[] = [
            'replyId' => $replyId,
            'parentCommentId' => $commentId,
            'threadId' => $threadId,
            'author' => (string) ($reply['author'] ?? ''),
            'authorChannelUrl' => '',
            'text' => (string) ($reply['text'] ?? ''),
            'likeCount' => (int) ($reply['likeCount'] ?? 0),
            'publishedAt' => (string) ($reply['publishedAt'] ?? ''),
            'updatedAt' => (string) ($reply['publishedAt'] ?? ''),
        ];

        return true;
    }

    /**
     * @param array<string, mixed> $snippet
     */
    public function appendReplySnippet(array $snippet, string $replyId, string $parentCommentId, string $threadId): bool
    {
        if ($replyId === '' || isset($this->replyIds[$replyId])) {
            return false;
        }

        $this->replyIds[$replyId] = true;
        $this->repliesIndex[] = [
            'replyId' => $replyId,
            'parentCommentId' => $parentCommentId,
            'threadId' => $threadId,
            'author' => (string) ($snippet['authorDisplayName'] ?? ''),
            'authorChannelUrl' => (string) ($snippet['authorChannelUrl'] ?? ''),
            'text' => (string) ($snippet['textDisplay'] ?? ''),
            'likeCount' => (int) ($snippet['likeCount'] ?? 0),
            'publishedAt' => (string) ($snippet['publishedAt'] ?? ''),
            'updatedAt' => (string) ($snippet['updatedAt'] ?? ''),
        ];

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'commentIds' => $this->commentIds,
            'replyIds' => $this->replyIds,
            'threadParentMap' => $this->threadParentMap,
            'commentsIndex' => $this->commentsIndex,
            'repliesIndex' => $this->repliesIndex,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function boolMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $key => $flag) {
            $normalized[(string) $key] = (bool) $flag;
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    private static function stringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[(string) $key] = (string) $item;
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function items(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_array($item)));
    }
}
