<?php

namespace App\Modules\Bluesky\Presenters;

final class BlueskyInteractionPresenter
{
    public function __construct(
        private readonly BlueskyActorPresenter $actorPresenter,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function presentLike(array $item): array
    {
        return [
            'actor' => $this->actorPresenter->present((array) ($item['actor'] ?? [])),
            'createdAt' => (string) ($item['createdAt'] ?? ''),
            'indexedAt' => (string) ($item['indexedAt'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function presentRepostActor(array $item): array
    {
        return [
            'actor' => $this->actorPresenter->present($item),
            'createdAt' => (string) ($item['createdAt'] ?? ''),
            'indexedAt' => (string) ($item['indexedAt'] ?? ''),
        ];
    }
}
