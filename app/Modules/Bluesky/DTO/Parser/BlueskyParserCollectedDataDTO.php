<?php

namespace App\Modules\Bluesky\DTO\Parser;

use App\Modules\Bluesky\Enums\BlueskyParserInteractionKind;
use App\Support\Contracts\ArrayPayloadable;

final class BlueskyParserCollectedDataDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed>|null $profile
     * @param array<string, bool> $postIds
     * @param array<string, bool> $authoredReplyIds
     * @param array<string, bool> $receivedReplyIds
     * @param array<string, bool> $followersIds
     * @param array<string, bool> $followsIds
     * @param array<string, bool> $reactionIds
     * @param array<int, array<string, mixed>> $postsIndex
     * @param array<int, array<string, mixed>> $authoredRepliesIndex
     * @param array<int, array<string, mixed>> $receivedRepliesIndex
     * @param array<int, array<string, mixed>> $followersIndex
     * @param array<int, array<string, mixed>> $followsIndex
     * @param array<int, array<string, mixed>> $reactionsIndex
     */
    public function __construct(
        private ?array $profile = null,
        private array $postIds = [],
        private array $authoredReplyIds = [],
        private array $receivedReplyIds = [],
        private array $followersIds = [],
        private array $followsIds = [],
        private array $reactionIds = [],
        private array $postsIndex = [],
        private array $authoredRepliesIndex = [],
        private array $receivedRepliesIndex = [],
        private array $followersIndex = [],
        private array $followsIndex = [],
        private array $reactionsIndex = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            profile: is_array($payload['profile'] ?? null) ? $payload['profile'] : null,
            postIds: self::boolMap($payload['postIds'] ?? []),
            authoredReplyIds: self::boolMap($payload['authoredReplyIds'] ?? []),
            receivedReplyIds: self::boolMap($payload['receivedReplyIds'] ?? []),
            followersIds: self::boolMap($payload['followersIds'] ?? []),
            followsIds: self::boolMap($payload['followsIds'] ?? []),
            reactionIds: self::boolMap($payload['reactionIds'] ?? []),
            postsIndex: self::itemList($payload['postsIndex'] ?? []),
            authoredRepliesIndex: self::itemList($payload['authoredRepliesIndex'] ?? []),
            receivedRepliesIndex: self::itemList($payload['receivedRepliesIndex'] ?? []),
            followersIndex: self::itemList($payload['followersIndex'] ?? []),
            followsIndex: self::itemList($payload['followsIndex'] ?? []),
            reactionsIndex: self::itemList($payload['reactionsIndex'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profile(): ?array
    {
        return $this->profile;
    }

    /**
     * @param array<string, mixed> $profile
     */
    public function setProfile(array $profile): void
    {
        $this->profile = $profile;
    }

    public function actorIdentifier(): string
    {
        return (string) ($this->profile['did'] ?? $this->profile['handle'] ?? '');
    }

    public function profileDid(): string
    {
        return (string) ($this->profile['did'] ?? '');
    }

    /**
     * @param array<string, mixed> $item
     */
    public function recordFeedItem(array $item): void
    {
        $uri = (string) ($item['uri'] ?? $item['id'] ?? '');
        if ($uri === '') {
            return;
        }

        $postType = (string) ($item['postType'] ?? 'post');

        if ($postType === 'reply') {
            if (isset($this->authoredReplyIds[$uri])) {
                return;
            }

            $this->authoredReplyIds[$uri] = true;
            $this->authoredRepliesIndex[] = $item;

            return;
        }

        if (isset($this->postIds[$uri])) {
            return;
        }

        $this->postIds[$uri] = true;
        $this->postsIndex[] = $item;
    }

    /**
     * @param array<string, mixed> $item
     */
    public function recordFollower(array $item): void
    {
        $did = (string) ($item['did'] ?? '');
        if ($did === '' || isset($this->followersIds[$did])) {
            return;
        }

        $this->followersIds[$did] = true;
        $this->followersIndex[] = $item;
    }

    /**
     * @param array<string, mixed> $item
     */
    public function recordFollow(array $item): void
    {
        $did = (string) ($item['did'] ?? '');
        if ($did === '' || isset($this->followsIds[$did])) {
            return;
        }

        $this->followsIds[$did] = true;
        $this->followsIndex[] = $item;
    }

    /**
     * @param array<string, mixed> $item
     */
    public function recordReaction(
        string $postUri,
        string $postCid,
        BlueskyParserInteractionKind $kind,
        array $item,
    ): void {
        $actor = is_array($item['actor'] ?? null) ? $item['actor'] : [];
        $did = (string) ($actor['did'] ?? '');
        if ($did === '') {
            return;
        }

        $reactionId = implode(':', [$postUri, $kind->value, $did, (string) ($item['createdAt'] ?? '')]);
        if (isset($this->reactionIds[$reactionId])) {
            return;
        }

        $this->reactionIds[$reactionId] = true;
        $this->reactionsIndex[] = [
            'postUri' => $postUri,
            'postCid' => $postCid,
            'kind' => rtrim($kind->value, 's'),
            'actor' => $actor,
            'createdAt' => (string) ($item['createdAt'] ?? ''),
            'indexedAt' => (string) ($item['indexedAt'] ?? ''),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $replies
     */
    public function recordReceivedReplies(string $rootPostUri, array $replies): void
    {
        foreach ($replies as $reply) {
            $replyUri = (string) ($reply['uri'] ?? '');

            if ($replyUri === '' || isset($this->receivedReplyIds[$replyUri])) {
                continue;
            }

            $this->receivedReplyIds[$replyUri] = true;
            $this->receivedRepliesIndex[] = [
                'rootPostUri' => $rootPostUri,
                ...$reply,
            ];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function authoredItems(): array
    {
        return [...$this->postsIndex, ...$this->authoredRepliesIndex];
    }

    public function postsCount(): int
    {
        return count($this->postsIndex);
    }

    public function authoredRepliesCount(): int
    {
        return count($this->authoredRepliesIndex);
    }

    public function receivedRepliesCount(): int
    {
        return count($this->receivedRepliesIndex);
    }

    public function followersCount(): int
    {
        return count($this->followersIndex);
    }

    public function followsCount(): int
    {
        return count($this->followsIndex);
    }

    public function reactionsCount(): int
    {
        return count($this->reactionsIndex);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function postsIndex(): array
    {
        return $this->postsIndex;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function authoredRepliesIndex(): array
    {
        return $this->authoredRepliesIndex;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function receivedRepliesIndex(): array
    {
        return $this->receivedRepliesIndex;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function followersIndex(): array
    {
        return $this->followersIndex;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function followsIndex(): array
    {
        return $this->followsIndex;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function reactionsIndex(): array
    {
        return $this->reactionsIndex;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'profile' => $this->profile,
            'postIds' => $this->postIds,
            'authoredReplyIds' => $this->authoredReplyIds,
            'receivedReplyIds' => $this->receivedReplyIds,
            'followersIds' => $this->followersIds,
            'followsIds' => $this->followsIds,
            'reactionIds' => $this->reactionIds,
            'postsIndex' => $this->postsIndex,
            'authoredRepliesIndex' => $this->authoredRepliesIndex,
            'receivedRepliesIndex' => $this->receivedRepliesIndex,
            'followersIndex' => $this->followersIndex,
            'followsIndex' => $this->followsIndex,
            'reactionsIndex' => $this->reactionsIndex,
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
    private static function itemList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_array($item),
        ));
    }
}
