<?php

namespace App\Modules\Mastodon\Analytics\Contracts;

use App\Modules\Mastodon\DTO\Request\MastodonAnalyticsQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAnalyticsResultDTO;

interface MastodonAnalyticsApplicationServiceInterface
{
    public function summary(MastodonAnalyticsQueryDTO $query): MastodonAnalyticsResultDTO;
}
