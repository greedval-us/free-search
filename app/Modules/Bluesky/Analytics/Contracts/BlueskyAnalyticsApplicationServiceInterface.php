<?php

namespace App\Modules\Bluesky\Analytics\Contracts;

use App\Modules\Bluesky\DTO\Request\BlueskyAnalyticsQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyAnalyticsResultDTO;

interface BlueskyAnalyticsApplicationServiceInterface
{
    public function summary(BlueskyAnalyticsQueryDTO $query): BlueskyAnalyticsResultDTO;
}
