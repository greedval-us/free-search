<?php

namespace App\Modules\Bluesky\Actions\Request;

use App\Modules\Bluesky\Actions\AbstractBlueskyAction;
use App\Modules\Bluesky\DTO\Result\BlueskyActorListResultDTO;
use App\Modules\Bluesky\Presenters\BlueskyInteractionPresenter;

final class LoadPostLikesAction extends AbstractBlueskyAction
{
    public function __construct(
        \App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface $gateway,
        private readonly BlueskyInteractionPresenter $interactionPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(string $uri, ?string $cid, int $limit, ?string $cursor = null): BlueskyActorListResultDTO
    {
        $payload = $this->gateway->getLikes($uri, $cid, $limit, $cursor);

        $items = collect($payload['likes'] ?? [])
            ->map(fn (array $item): array => $this->interactionPresenter->presentLike($item))
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
                'uri' => $uri,
                'kind' => 'likes',
            ],
        );
    }

    private function normalizedCursor(mixed $value): ?string
    {
        $cursor = trim((string) $value);

        return $cursor !== '' ? $cursor : null;
    }
}
