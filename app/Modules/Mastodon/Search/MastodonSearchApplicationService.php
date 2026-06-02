<?php

namespace App\Modules\Mastodon\Search;

use App\Modules\Mastodon\Actions\Request\SearchResourcesAction;
use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAccountFollowersResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAccountStatusesResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonSearchResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonStatusContextResultDTO;
use App\Modules\Mastodon\Search\Contracts\MastodonSearchApplicationServiceInterface;

final class MastodonSearchApplicationService implements MastodonSearchApplicationServiceInterface
{
    public function __construct(
        private readonly SearchResourcesAction $searchResourcesAction,
        private readonly \App\Modules\Mastodon\Actions\Request\LoadStatusContextAction $loadStatusContextAction,
        private readonly \App\Modules\Mastodon\Actions\Request\LoadAccountStatusesAction $loadAccountStatusesAction,
        private readonly \App\Modules\Mastodon\Actions\Request\LoadAccountFollowersAction $loadAccountFollowersAction,
    ) {
    }

    public function search(MastodonSearchQueryDTO $query): MastodonSearchResultDTO
    {
        return $this->searchResourcesAction->handle($query);
    }

    public function context(string $statusId): MastodonStatusContextResultDTO
    {
        return $this->loadStatusContextAction->handle($statusId);
    }

    public function accountStatuses(string $accountId, int $limit): MastodonAccountStatusesResultDTO
    {
        return $this->loadAccountStatusesAction->handle($accountId, $limit);
    }

    public function accountFollowers(string $accountId, int $limit): MastodonAccountFollowersResultDTO
    {
        return $this->loadAccountFollowersAction->handle($accountId, $limit);
    }
}
