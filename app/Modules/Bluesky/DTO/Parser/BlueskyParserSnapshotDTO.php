<?php

namespace App\Modules\Bluesky\DTO\Parser;

use App\Support\Contracts\ArrayPayloadable;

final readonly class BlueskyParserSnapshotDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed>|null $resolvedActor
     * @param array<int, array<string, mixed>> $postsIndex
     * @param array<int, array<string, mixed>> $authoredRepliesIndex
     * @param array<int, array<string, mixed>> $receivedRepliesIndex
     * @param array<int, array<string, mixed>> $followersIndex
     * @param array<int, array<string, mixed>> $followsIndex
     * @param array<int, array<string, mixed>> $reactionsIndex
     */
    public function __construct(
        private string $actor,
        private ?array $resolvedActor,
        private array $postsIndex,
        private array $authoredRepliesIndex,
        private array $receivedRepliesIndex,
        private array $followersIndex,
        private array $followsIndex,
        private array $reactionsIndex,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'actor' => $this->actor,
            'resolvedActor' => $this->resolvedActor,
            'postsCount' => count($this->postsIndex),
            'authoredRepliesCount' => count($this->authoredRepliesIndex),
            'receivedRepliesCount' => count($this->receivedRepliesIndex),
            'followersCount' => count($this->followersIndex),
            'followsCount' => count($this->followsIndex),
            'reactionsCount' => count($this->reactionsIndex),
            'postsIndex' => $this->postsIndex,
            'authoredRepliesIndex' => $this->authoredRepliesIndex,
            'receivedRepliesIndex' => $this->receivedRepliesIndex,
            'followersIndex' => $this->followersIndex,
            'followsIndex' => $this->followsIndex,
            'reactionsIndex' => $this->reactionsIndex,
        ];
    }
}
