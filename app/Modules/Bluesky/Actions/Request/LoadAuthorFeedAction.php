<?php

namespace App\Modules\Bluesky\Actions\Request;

use App\Modules\Bluesky\Actions\AbstractBlueskyAction;
use App\Modules\Bluesky\DTO\Result\BlueskyPostListResultDTO;
use App\Modules\Bluesky\Presenters\BlueskyPostPresenter;

final class LoadAuthorFeedAction extends AbstractBlueskyAction
{
    public function __construct(
        \App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface $gateway,
        private readonly BlueskyPostPresenter $postPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(string $actor, int $limit, ?string $cursor = null, ?string $filter = null): BlueskyPostListResultDTO
    {
        $payload = $this->gateway->getAuthorFeed($actor, $limit, $cursor, $filter);

        $items = collect($payload['feed'] ?? [])
            ->map(function (array $item): ?array {
                $post = (array) ($item['post'] ?? []);

                return $post !== [] ? $this->postPresenter->present($post) : null;
            })
            ->filter()
            ->values()
            ->all();

        return new BlueskyPostListResultDTO(
            items: $items,
            pagination: [
                'limit' => $limit,
                'cursor' => $cursor,
                'nextCursor' => $this->normalizedCursor($payload['cursor'] ?? null),
                'hasMore' => $this->normalizedCursor($payload['cursor'] ?? null) !== null,
            ],
            meta: [
                'actor' => $actor,
                'filter' => $filter,
            ],
        );
    }

    private function normalizedCursor(mixed $value): ?string
    {
        $cursor = trim((string) $value);

        return $cursor !== '' ? $cursor : null;
    }
}
