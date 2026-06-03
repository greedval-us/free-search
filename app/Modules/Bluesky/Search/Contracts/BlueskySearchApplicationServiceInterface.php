<?php

namespace App\Modules\Bluesky\Search\Contracts;

use App\Modules\Bluesky\DTO\Request\BlueskySearchQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskySearchResultDTO;

interface BlueskySearchApplicationServiceInterface
{
    public function search(BlueskySearchQueryDTO $query): BlueskySearchResultDTO;
}
