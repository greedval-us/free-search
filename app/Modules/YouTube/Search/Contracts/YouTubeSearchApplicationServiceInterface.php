<?php

namespace App\Modules\YouTube\Search\Contracts;

use App\Modules\YouTube\DTO\Request\YouTubeSearchQueryDTO;
use App\Modules\YouTube\DTO\Result\YouTubeSearchResultDTO;

interface YouTubeSearchApplicationServiceInterface
{
    public function searchVideos(YouTubeSearchQueryDTO $query): YouTubeSearchResultDTO;
}
