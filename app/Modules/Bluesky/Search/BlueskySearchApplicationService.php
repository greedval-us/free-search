<?php

namespace App\Modules\Bluesky\Search;

use App\Modules\Bluesky\Actions\Request\SearchContentAction;
use App\Modules\Bluesky\DTO\Request\BlueskySearchQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskySearchResultDTO;
use App\Modules\Bluesky\Search\Contracts\BlueskySearchApplicationServiceInterface;

final class BlueskySearchApplicationService implements BlueskySearchApplicationServiceInterface
{
    public function __construct(
        private readonly SearchContentAction $searchContentAction,
    ) {
    }

    public function search(BlueskySearchQueryDTO $query): BlueskySearchResultDTO
    {
        return $this->searchContentAction->handle($query);
    }
}
