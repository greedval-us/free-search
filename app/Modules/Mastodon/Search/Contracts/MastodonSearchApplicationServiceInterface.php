<?php

namespace App\Modules\Mastodon\Search\Contracts;

use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAccountFollowersResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAccountStatusesResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonSearchResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonStatusContextResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonTagTimelineResultDTO;

interface MastodonSearchApplicationServiceInterface
{
    public function search(MastodonSearchQueryDTO $query): MastodonSearchResultDTO;

    public function context(string $statusId): MastodonStatusContextResultDTO;

    public function accountStatuses(string $accountId, int $limit, ?string $maxId = null): MastodonAccountStatusesResultDTO;

    public function accountFollowers(string $accountId, int $limit, ?string $maxId = null): MastodonAccountFollowersResultDTO;

    public function tagTimeline(string $tagName, int $limit, ?string $maxId = null): MastodonTagTimelineResultDTO;
}
