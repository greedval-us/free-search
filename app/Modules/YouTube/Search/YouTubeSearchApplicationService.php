<?php

namespace App\Modules\YouTube\Search;

use App\Modules\YouTube\Actions\Request\SearchVideosAction;
use App\Modules\YouTube\DTO\Request\YouTubeSearchQueryDTO;
use App\Modules\YouTube\Search\Contracts\YouTubeSearchApplicationServiceInterface;

class YouTubeSearchApplicationService implements YouTubeSearchApplicationServiceInterface
{
    public function __construct(private readonly SearchVideosAction $searchVideosAction) {}

    /**
     * @return array<string, mixed>
     */
    public function searchVideos(YouTubeSearchQueryDTO $query): array
    {
        return $this->searchVideosAction->handle($query);
    }
}
