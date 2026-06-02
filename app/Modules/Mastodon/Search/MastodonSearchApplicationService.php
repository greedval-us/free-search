<?php

namespace App\Modules\Mastodon\Search;

use App\Modules\Mastodon\Actions\Request\LoadAccountFollowersAction;
use App\Modules\Mastodon\Actions\Request\LoadAccountStatusesAction;
use App\Modules\Mastodon\Actions\Request\LoadStatusContextAction;
use App\Modules\Mastodon\Actions\Request\LoadTagTimelineAction;
use App\Modules\Mastodon\Actions\Request\SearchResourcesAction;
use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAccountFollowersResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAccountStatusesResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonSearchResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonStatusContextResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonTagTimelineResultDTO;
use App\Modules\Mastodon\Search\Contracts\MastodonSearchApplicationServiceInterface;

final class MastodonSearchApplicationService implements MastodonSearchApplicationServiceInterface
{
    public function __construct(
        private readonly SearchResourcesAction $searchResourcesAction,
        private readonly LoadStatusContextAction $loadStatusContextAction,
        private readonly LoadAccountStatusesAction $loadAccountStatusesAction,
        private readonly LoadAccountFollowersAction $loadAccountFollowersAction,
        private readonly LoadTagTimelineAction $loadTagTimelineAction,
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

    public function accountStatuses(string $accountId, int $limit, ?string $maxId = null): MastodonAccountStatusesResultDTO
    {
        return $this->loadAccountStatusesAction->handle($accountId, $limit, $maxId);
    }

    public function accountFollowers(string $accountId, int $limit, ?string $maxId = null): MastodonAccountFollowersResultDTO
    {
        return $this->loadAccountFollowersAction->handle($accountId, $limit, $maxId);
    }

    public function tagTimeline(string $tagName, int $limit, ?string $maxId = null): MastodonTagTimelineResultDTO
    {
        return $this->loadTagTimelineAction->handle($tagName, $limit, $maxId);
    }
}
