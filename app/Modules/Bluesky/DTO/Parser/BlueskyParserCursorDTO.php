<?php

namespace App\Modules\Bluesky\DTO\Parser;

use App\Modules\Bluesky\Enums\BlueskyParserInteractionKind;
use App\Support\Contracts\ArrayPayloadable;

final class BlueskyParserCursorDTO implements ArrayPayloadable
{
    public function __construct(
        private ?string $feedCursor = null,
        private int $feedPage = 0,
        private ?string $followersCursor = null,
        private int $followersPage = 0,
        private ?string $followsCursor = null,
        private int $followsPage = 0,
        private int $interactionPostIndex = 0,
        private BlueskyParserInteractionKind $interactionKind = BlueskyParserInteractionKind::Likes,
        private ?string $interactionCursor = null,
        private int $nextAdvanceAt = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            feedCursor: self::nullableString($payload['feedCursor'] ?? null),
            feedPage: max(0, (int) ($payload['feedPage'] ?? 0)),
            followersCursor: self::nullableString($payload['followersCursor'] ?? null),
            followersPage: max(0, (int) ($payload['followersPage'] ?? 0)),
            followsCursor: self::nullableString($payload['followsCursor'] ?? null),
            followsPage: max(0, (int) ($payload['followsPage'] ?? 0)),
            interactionPostIndex: max(0, (int) ($payload['interactionPostIndex'] ?? 0)),
            interactionKind: BlueskyParserInteractionKind::tryFrom((string) ($payload['interactionKind'] ?? ''))
                ?? BlueskyParserInteractionKind::Likes,
            interactionCursor: self::nullableString($payload['interactionCursor'] ?? null),
            nextAdvanceAt: max(0, (int) ($payload['nextAdvanceAt'] ?? 0)),
        );
    }

    public function feedCursor(): ?string
    {
        return $this->feedCursor;
    }

    public function setFeedCursor(?string $cursor): void
    {
        $this->feedCursor = self::nullableString($cursor);
    }

    public function incrementFeedPage(): int
    {
        return ++$this->feedPage;
    }

    public function followersCursor(): ?string
    {
        return $this->followersCursor;
    }

    public function setFollowersCursor(?string $cursor): void
    {
        $this->followersCursor = self::nullableString($cursor);
    }

    public function incrementFollowersPage(): int
    {
        return ++$this->followersPage;
    }

    public function followsCursor(): ?string
    {
        return $this->followsCursor;
    }

    public function setFollowsCursor(?string $cursor): void
    {
        $this->followsCursor = self::nullableString($cursor);
    }

    public function incrementFollowsPage(): int
    {
        return ++$this->followsPage;
    }

    public function interactionPostIndex(): int
    {
        return $this->interactionPostIndex;
    }

    public function setInteractionPostIndex(int $index): void
    {
        $this->interactionPostIndex = max(0, $index);
    }

    public function interactionKind(): BlueskyParserInteractionKind
    {
        return $this->interactionKind;
    }

    public function setInteractionKind(BlueskyParserInteractionKind $kind): void
    {
        $this->interactionKind = $kind;
    }

    public function interactionCursor(): ?string
    {
        return $this->interactionCursor;
    }

    public function setInteractionCursor(?string $cursor): void
    {
        $this->interactionCursor = self::nullableString($cursor);
    }

    public function nextAdvanceAt(): int
    {
        return $this->nextAdvanceAt;
    }

    public function setNextAdvanceAt(int $timestamp): void
    {
        $this->nextAdvanceAt = max(0, $timestamp);
    }

    public function resetInteraction(int $postIndex = 0): void
    {
        $this->interactionPostIndex = max(0, $postIndex);
        $this->interactionKind = BlueskyParserInteractionKind::Likes;
        $this->interactionCursor = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'feedCursor' => $this->feedCursor ?? '',
            'feedPage' => $this->feedPage,
            'followersCursor' => $this->followersCursor ?? '',
            'followersPage' => $this->followersPage,
            'followsCursor' => $this->followsCursor ?? '',
            'followsPage' => $this->followsPage,
            'interactionPostIndex' => $this->interactionPostIndex,
            'interactionKind' => $this->interactionKind->value,
            'interactionCursor' => $this->interactionCursor ?? '',
            'nextAdvanceAt' => $this->nextAdvanceAt,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
