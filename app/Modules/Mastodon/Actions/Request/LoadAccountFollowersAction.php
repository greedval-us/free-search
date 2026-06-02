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

    public function handle(string $accountId, int $limit): MastodonAccountFollowersResultDTO
    {
        $payload = $this->gateway->accountFollowers($accountId, $limit);

        return new MastodonAccountFollowersResultDTO(
            accounts: collect($payload)
                ->map(fn (array $item): array => $this->accountPresenter->present($item))
                ->values()
                ->all(),
        );
    }
}
