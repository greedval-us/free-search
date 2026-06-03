<?php

namespace App\Modules\Bluesky\Actions\Request;

use App\Modules\Bluesky\Actions\AbstractBlueskyAction;
use App\Modules\Bluesky\DTO\Result\BlueskyActorListResultDTO;
use App\Modules\Bluesky\Presenters\BlueskyInteractionPresenter;

final class LoadPostRepostsAction extends AbstractBlueskyAction
{
    public function __construct(
        \App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface $gateway,
        private readonly BlueskyInteractionPresenter $interactionPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(string $uri, int $limit, ?string $cursor = null): BlueskyActorListResultDTO
    {
        $payload = $this->gateway->getRepostedBy($uri, $limit, $cursor);

        $items = collect($payload['repostedBy'] ?? [])
            ->map(fn (array $item): array => $this->interactionPresenter->presentRepostActor($item))
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
                'kind' => 'reposts',
            ],
        );
    }

    private function normalizedCursor(mixed $value): ?string
    {
        $cursor = trim((string) $value);

        return $cursor !== '' ? $cursor : null;
    }
}
