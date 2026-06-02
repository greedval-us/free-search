<?php

namespace App\Modules\Mastodon\Search\Contracts;

use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonSearchResultDTO;

interface MastodonSearchApplicationServiceInterface
{
    public function search(MastodonSearchQueryDTO $query): MastodonSearchResultDTO;
}
