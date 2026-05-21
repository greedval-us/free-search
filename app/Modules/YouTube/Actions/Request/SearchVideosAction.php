<?php

namespace App\Modules\YouTube\Actions\Request;

use App\Modules\YouTube\Actions\AbstractYouTubeAction;
use App\Modules\YouTube\DTO\Request\YouTubeSearchQueryDTO;

class SearchVideosAction extends AbstractYouTubeAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(YouTubeSearchQueryDTO $query): array
    {
        return $this->gateway->searchVideos($query->toArray());
    }
}
