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

    public function handle(string $accountId, int $limit, ?string $maxId = null): MastodonAccountStatusesResultDTO
    {
        $payload = $this->gateway->accountStatuses($accountId, $limit, $maxId);
        $statuses = collect($payload['items'] ?? [])
            ->map(fn (array $item): array => $this->statusPresenter->present($item))
            ->values()
            ->all();
        $nextMaxId = data_get($payload, 'pagination.nextMaxId');
        $hasMore = is_string($nextMaxId) && $nextMaxId !== '';

        return new MastodonAccountStatusesResultDTO(
            statuses: $statuses,
            pagination: [
                'limit' => $limit,
                'maxId' => $maxId,
                'nextMaxId' => $hasMore ? $nextMaxId : null,
                'hasMore' => $hasMore,
            ],
        );
    }
}
