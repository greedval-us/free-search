<?php

namespace App\Modules\Mastodon\Actions\Request;

use App\Modules\Mastodon\Actions\AbstractMastodonAction;
use App\Modules\Mastodon\DTO\Result\MastodonAccountStatusesResultDTO;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;

final class LoadAccountStatusesAction extends AbstractMastodonAction
{
    public function __construct(
        \App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface $gateway,
        private readonly MastodonStatusPresenter $statusPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(string $accountId, int $limit): MastodonAccountStatusesResultDTO
    {
        $payload = $this->gateway->accountStatuses($accountId, $limit);

        return new MastodonAccountStatusesResultDTO(
            statuses: collect($payload)
                ->map(fn (array $item): array => $this->statusPresenter->present($item))
                ->values()
                ->all(),
        );
    }
}
