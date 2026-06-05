<?php

namespace App\Modules\Mastodon\DTO\Parser;

use App\Modules\Mastodon\Enums\MastodonPostType;
use App\Support\Contracts\ArrayPayloadable;

final class MastodonParserCollectedDataDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed>|null $account
     * @param array<string, bool> $statusIds
     * @param array<string, bool> $commentIds
     * @param array<int, array<string, mixed>> $statusesIndex
     * @param array<int, array<string, mixed>> $commentsIndex
     */
    public function __construct(
        private ?array $account = null,
        private array $statusIds = [],
        private array $commentIds = [],
        private array $statusesIndex = [],
        private array $commentsIndex = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            account: is_array($payload['account'] ?? null) ? $payload['account'] : null,
            statusIds: self::boolMap($payload['statusIds'] ?? []),
            commentIds: self::boolMap($payload['commentIds'] ?? []),
            statusesIndex: self::items($payload['statusesIndex'] ?? []),
            commentsIndex: self::items($payload['commentsIndex'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function account(): ?array
    {
        return $this->account;
    }

    /**
     * @param array<string, mixed> $account
     */
    public function setAccount(array $account): void
    {
        $this->account = $account;
    }

    public function accountId(): string
    {
        return (string) ($this->account['id'] ?? '');
    }

    public function statusesCount(): int
    {
        return count($this->statusesIndex);
    }

    public function commentsCount(): int
    {
        return count($this->commentsIndex);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function statusesIndex(): array
    {
        return $this->statusesIndex;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function commentsIndex(): array
    {
        return $this->commentsIndex;
    }

    /**
     * @param array<string, mixed> $presented
     */
    public function appendStatus(array $presented): bool
    {
        $statusId = (string) ($presented['id'] ?? '');

        if ($statusId === '' || isset($this->statusIds[$statusId])) {
            return false;
        }

        $this->statusIds[$statusId] = true;
        $this->statusesIndex[] = $presented;

        return true;
    }

    /**
     * @param array<string, mixed> $presented
     */
    public function shouldLoadCommentsForStatus(array $presented): bool
    {
        return (string) ($presented['postType'] ?? '') === MastodonPostType::Original->value
            && (int) ($presented['repliesCount'] ?? 0) > 0
            && (string) ($presented['id'] ?? '') !== '';
    }

    /**
     * @param array<string, mixed> $presented
     */
    public function appendComment(string $rootStatusId, array $presented): void
    {
        $commentId = (string) ($presented['id'] ?? '');

        if ($commentId === '') {
            return;
        }

        $compositeId = $rootStatusId . ':' . $commentId;
        if (isset($this->commentIds[$compositeId])) {
            return;
        }

        $this->commentIds[$compositeId] = true;
        $this->commentsIndex[] = [
            'rootStatusId' => $rootStatusId,
            'commentId' => $commentId,
            'parentStatusId' => $presented['inReplyToId'] ?? null,
            'createdAt' => (string) ($presented['createdAt'] ?? ''),
            'content' => (string) ($presented['content'] ?? ''),
            'spoilerText' => (string) ($presented['spoilerText'] ?? ''),
            'language' => (string) ($presented['language'] ?? ''),
            'visibility' => (string) ($presented['visibility'] ?? ''),
            'sensitive' => (bool) ($presented['sensitive'] ?? false),
            'repliesCount' => (int) ($presented['repliesCount'] ?? 0),
            'reblogsCount' => (int) ($presented['reblogsCount'] ?? 0),
            'favouritesCount' => (int) ($presented['favouritesCount'] ?? 0),
            'hasMedia' => (bool) ($presented['hasMedia'] ?? false),
            'hasLinks' => (bool) ($presented['hasLinks'] ?? false),
            'postType' => (string) ($presented['postType'] ?? ''),
            'url' => (string) ($presented['url'] ?? ''),
            'links' => is_array($presented['links'] ?? null) ? $presented['links'] : [],
            'domains' => is_array($presented['domains'] ?? null) ? $presented['domains'] : [],
            'tags' => is_array($presented['tags'] ?? null) ? $presented['tags'] : [],
            'mentions' => is_array($presented['mentions'] ?? null) ? $presented['mentions'] : [],
            'account' => is_array($presented['account'] ?? null) ? $presented['account'] : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account' => $this->account,
            'statusIds' => $this->statusIds,
            'commentIds' => $this->commentIds,
            'statusesIndex' => $this->statusesIndex,
            'commentsIndex' => $this->commentsIndex,
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
