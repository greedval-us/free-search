<?php

namespace App\Modules\Mastodon\DTO\Parser;

use App\Support\Contracts\ArrayPayloadable;

final class MastodonParserCursorDTO implements ArrayPayloadable
{
    /**
     * @param array<int, string> $commentStatusIds
     */
    public function __construct(
        private ?string $statusesMaxId = null,
        private int $statusesPage = 0,
        private int $statusesTotalHint = 0,
        private array $commentStatusIds = [],
        private int $commentStatusIndex = 0,
        private int $nextAdvanceAt = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            statusesMaxId: self::nullableString($payload['statusesMaxId'] ?? null),
            statusesPage: max(0, (int) ($payload['statusesPage'] ?? 0)),
            statusesTotalHint: max(0, (int) ($payload['statusesTotalHint'] ?? 0)),
            commentStatusIds: array_values(array_filter(
                is_array($payload['commentStatusIds'] ?? null) ? $payload['commentStatusIds'] : [],
                static fn (mixed $value): bool => trim((string) $value) !== '',
            )),
            commentStatusIndex: max(0, (int) ($payload['commentStatusIndex'] ?? 0)),
            nextAdvanceAt: max(0, (int) ($payload['nextAdvanceAt'] ?? 0)),
        );
    }

    public function statusesMaxId(): ?string
    {
        return $this->statusesMaxId;
    }

    public function setStatusesMaxId(?string $value): void
    {
        $this->statusesMaxId = self::nullableString($value);
    }

    public function incrementStatusesPage(): int
    {
        return ++$this->statusesPage;
    }

    public function statusesPage(): int
    {
        return $this->statusesPage;
    }

    public function statusesTotalHint(): int
    {
        return $this->statusesTotalHint;
    }

    public function setStatusesTotalHint(int $value): void
    {
        $this->statusesTotalHint = max(0, $value);
    }

    /**
     * @return array<int, string>
     */
    public function commentStatusIds(): array
    {
        return $this->commentStatusIds;
    }

    /**
     * @param array<int, string> $ids
     */
    public function setCommentStatusIds(array $ids): void
    {
        $normalized = [];

        foreach ($ids as $id) {
            $value = trim((string) $id);

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        $this->commentStatusIds = array_values(array_unique($normalized));
    }

    public function commentStatusIndex(): int
    {
        return $this->commentStatusIndex;
    }

    public function setCommentStatusIndex(int $index): void
    {
        $this->commentStatusIndex = max(0, $index);
    }

    public function incrementCommentStatusIndex(): int
    {
        return ++$this->commentStatusIndex;
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
            'statusesMaxId' => $this->statusesMaxId ?? '',
            'statusesPage' => $this->statusesPage,
            'statusesTotalHint' => $this->statusesTotalHint,
            'commentStatusIds' => $this->commentStatusIds,
            'commentStatusIndex' => $this->commentStatusIndex,
            'nextAdvanceAt' => $this->nextAdvanceAt,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
