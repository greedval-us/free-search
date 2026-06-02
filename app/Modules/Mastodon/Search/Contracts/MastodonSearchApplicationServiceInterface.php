<?php

namespace App\Modules\Mastodon\Search\Contracts;

use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonSearchResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonStatusContextResultDTO;

interface MastodonSearchApplicationServiceInterface
{
    public function search(MastodonSearchQueryDTO $query): MastodonSearchResultDTO;

    public function context(string $statusId): MastodonStatusContextResultDTO;
}
