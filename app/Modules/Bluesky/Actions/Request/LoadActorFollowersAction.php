<?php

namespace App\Modules\Bluesky\Actions\Request;

use App\Modules\Bluesky\Actions\AbstractBlueskyAction;
use App\Modules\Bluesky\DTO\Result\BlueskyActorListResultDTO;
use App\Modules\Bluesky\Presenters\BlueskyActorPresenter;

final class LoadActorFollowersAction extends AbstractBlueskyAction
{
    public function __construct(
        \App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface $gateway,
        private readonly BlueskyActorPresenter $actorPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(string $actor, int $limit, ?string $cursor = null): BlueskyActorListResultDTO
    {
        $payload = $this->gateway->getFollowers($actor, $limit, $cursor);

        $items = collect($payload['followers'] ?? [])
            ->map(fn (array $item): array => $this->actorPresenter->present($item))
            ->values()
            ->all();

        return new BlueskyActorListResultDTO(
            items: $items,
            pagination: [
                'limit' => $limit,
                'cursor' => $cursor,
                'nextCursor' => $this->normalizedCursor($payload['cursor'] ?? null),
                'hasMore' => $this->normalizedCursor($payload['cursor'] ?? null) !== null,
            ],
            meta: [
                'actor' => $actor,
                'kind' => 'followers',
            ],
        );
    }

    private function normalizedCursor(mixed $value): ?string
    {
        $cursor = trim((string) $value);

        return $cursor !== '' ? $cursor : null;
    }
}
