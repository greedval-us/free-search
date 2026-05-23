<?php

namespace App\Modules\YouTube\Search\Contracts;

use App\Modules\YouTube\DTO\Request\YouTubeSearchQueryDTO;

interface YouTubeSearchApplicationServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function searchVideos(YouTubeSearchQueryDTO $query): array;
}
