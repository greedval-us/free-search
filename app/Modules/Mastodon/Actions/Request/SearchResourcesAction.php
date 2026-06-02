<?php

namespace App\Modules\Mastodon\Actions\Request;

use App\Modules\Mastodon\Actions\AbstractMastodonAction;
use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonSearchResultDTO;
use App\Modules\Mastodon\Presenters\MastodonAccountPresenter;
use App\Modules\Mastodon\Presenters\MastodonHashtagPresenter;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;

final class SearchResourcesAction extends AbstractMastodonAction
{
    public function __construct(
        \App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface $gateway,
        private readonly MastodonStatusPresenter $statusPresenter,
        private readonly MastodonAccountPresenter $accountPresenter,
        private readonly MastodonHashtagPresenter $hashtagPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(MastodonSearchQueryDTO $query): MastodonSearchResultDTO
    {
        $payload = $this->gateway->search($query->toArray());

        $statuses = collect($payload['statuses'] ?? [])
            ->map(fn (array $item): array => $this->statusPresenter->present($item))
            ->values()
            ->all();

        $accounts = collect($payload['accounts'] ?? [])
            ->map(fn (array $item): array => $this->accountPresenter->present($item))
            ->values()
            ->all();

        $hashtags = collect($payload['hashtags'] ?? [])
            ->map(fn (array $item): array => $this->hashtagPresenter->present($item))
            ->values()
            ->all();

        $largestBucket = max(count($statuses), count($accounts), count($hashtags));
        $hasMore = $largestBucket >= $query->limit;

        return new MastodonSearchResultDTO(
            statuses: $statuses,
            accounts: $accounts,
            hashtags: $hashtags,
            pagination: [
                'query' => $query->query,
                'type' => $query->type,
                'limit' => $query->limit,
                'offset' => $query->offset,
                'nextOffset' => $hasMore ? $query->offset + $query->limit : null,
                'hasMore' => $hasMore,
            ],
        );
    }
}
