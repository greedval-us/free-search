<?php

namespace App\Modules\Mastodon\Actions\Request;

use App\Modules\Mastodon\Actions\AbstractMastodonAction;
use App\Modules\Mastodon\DTO\Result\MastodonStatusContextResultDTO;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;

final class LoadStatusContextAction extends AbstractMastodonAction
{
    public function __construct(
        \App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface $gateway,
        private readonly MastodonStatusPresenter $statusPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(string $statusId): MastodonStatusContextResultDTO
    {
        $payload = $this->gateway->context($statusId);
        $descendants = collect($payload['descendants'] ?? [])
            ->map(fn (array $item): array => $this->statusPresenter->present($item))
            ->values()
            ->all();

        return new MastodonStatusContextResultDTO(
            ancestors: collect($payload['ancestors'] ?? [])
                ->map(fn (array $item): array => $this->statusPresenter->present($item))
                ->values()
                ->all(),
            descendants: $descendants,
            descendantsTree: $this->buildDescendantsTree($descendants, $statusId),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $descendants
     * @return array<int, array<string, mixed>>
     */
    private function buildDescendantsTree(array $descendants, string $rootStatusId): array
    {
        if ($descendants === []) {
            return [];
        }

        $descendantIds = collect($descendants)
            ->map(fn (array $item): string => (string) ($item['id'] ?? ''))
            ->filter()
            ->flip();

        $groupedByParent = collect($descendants)
            ->groupBy(fn (array $item): string => (string) ($item['inReplyToId'] ?? ''));

        $rootReplies = collect($descendants)
            ->filter(function (array $item) use ($rootStatusId, $descendantIds): bool {
                $parentId = (string) ($item['inReplyToId'] ?? '');

                return $parentId === $rootStatusId
                    || $parentId === ''
                    || ! $descendantIds->has($parentId);
            })
            ->values();

        return $rootReplies
            ->map(fn (array $item): array => $this->attachReplies($item, $groupedByParent))
            ->all();
    }

    /**
     * @param array<string, mixed> $item
     * @param \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, array<string, mixed>>> $groupedByParent
     * @return array<string, mixed>
     */
    private function attachReplies(array $item, \Illuminate\Support\Collection $groupedByParent): array
    {
        $children = $groupedByParent
            ->get((string) ($item['id'] ?? ''), collect())
            ->map(fn (array $child): array => $this->attachReplies($child, $groupedByParent))
            ->values()
            ->all();

        $item['replies'] = $children;

        return $item;
    }
}
