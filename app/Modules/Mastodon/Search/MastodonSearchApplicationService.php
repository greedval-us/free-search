<?php

namespace App\Modules\Mastodon\Search;

use App\Modules\Mastodon\Actions\Request\SearchResourcesAction;
use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonSearchResultDTO;
use App\Modules\Mastodon\Search\Contracts\MastodonSearchApplicationServiceInterface;

final class MastodonSearchApplicationService implements MastodonSearchApplicationServiceInterface
{
    public function __construct(
        private readonly SearchResourcesAction $searchResourcesAction,
    ) {
    }

    public function search(MastodonSearchQueryDTO $query): MastodonSearchResultDTO
    {
        return $this->searchResourcesAction->handle($query);
    }
}
