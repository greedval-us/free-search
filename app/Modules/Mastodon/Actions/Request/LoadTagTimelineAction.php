<?php

namespace App\Modules\Mastodon\Actions\Request;

use App\Modules\Mastodon\Actions\AbstractMastodonAction;
use App\Modules\Mastodon\DTO\Result\MastodonTagTimelineResultDTO;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;

final class LoadTagTimelineAction extends AbstractMastodonAction
{
    public function __construct(
        \App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface $gateway,
        private readonly MastodonStatusPresenter $statusPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(string $tagName, int $limit, ?string $maxId = null): MastodonTagTimelineResultDTO
    {
        $payload = $this->gateway->tagTimeline($tagName, $limit, $maxId);
        $statuses = collect($payload['items'] ?? [])
            ->map(fn (array $item): array => $this->statusPresenter->present($item))
            ->values()
            ->all();
        $nextMaxId = data_get($payload, 'pagination.nextMaxId');
        $hasMore = is_string($nextMaxId) && $nextMaxId !== '';

        return new MastodonTagTimelineResultDTO(
            statuses: $statuses,
            analytics: $this->buildAnalytics($statuses),
            pagination: [
                'limit' => $limit,
                'maxId' => $maxId,
                'nextMaxId' => $hasMore ? $nextMaxId : null,
                'hasMore' => $hasMore,
            ],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $statuses
     * @return array<string, mixed>
     */
    private function buildAnalytics(array $statuses): array
    {
        $uniqueAccounts = collect($statuses)
            ->map(fn (array $status): array => (array) ($status['account'] ?? []))
            ->filter(fn (array $account): bool => (string) ($account['id'] ?? '') !== '')
            ->unique(fn (array $account): string => (string) ($account['id'] ?? ''))
            ->values();

        $instanceDomains = $uniqueAccounts
            ->map(fn (array $account): string => (string) ($account['instanceDomain'] ?? ''))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'uniqueAccountsCount' => $uniqueAccounts->count(),
            'uniqueAccounts' => $uniqueAccounts->all(),
            'uniqueInstancesCount' => count($instanceDomains),
            'instanceDomains' => $instanceDomains,
            'postsWithMediaCount' => collect($statuses)->where('hasMedia', true)->count(),
            'postsWithLinksCount' => collect($statuses)->where('hasLinks', true)->count(),
        ];
    }
}
