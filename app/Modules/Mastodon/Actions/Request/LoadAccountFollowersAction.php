<?php

namespace App\Modules\Mastodon\Actions\Request;

use App\Modules\Mastodon\Actions\AbstractMastodonAction;
use App\Modules\Mastodon\DTO\Result\MastodonAccountFollowersResultDTO;
use App\Modules\Mastodon\Presenters\MastodonAccountPresenter;

final class LoadAccountFollowersAction extends AbstractMastodonAction
{
    public function __construct(
        \App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface $gateway,
        private readonly MastodonAccountPresenter $accountPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(string $accountId, int $limit, ?string $maxId = null): MastodonAccountFollowersResultDTO
    {
        $payload = $this->gateway->accountFollowers($accountId, $limit, $maxId);
        $accounts = collect($payload['items'] ?? [])
            ->map(fn (array $item): array => $this->accountPresenter->present($item))
            ->values()
            ->all();
        $nextMaxId = data_get($payload, 'pagination.nextMaxId');
        $hasMore = is_string($nextMaxId) && $nextMaxId !== '';

        return new MastodonAccountFollowersResultDTO(
            accounts: $accounts,
            pagination: [
                'limit' => $limit,
                'maxId' => $maxId,
                'nextMaxId' => $hasMore ? $nextMaxId : null,
                'hasMore' => $hasMore,
            ],
        );
    }
}
