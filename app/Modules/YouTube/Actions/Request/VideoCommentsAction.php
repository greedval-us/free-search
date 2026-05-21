<?php

namespace App\Modules\YouTube\Actions\Request;

use App\Modules\YouTube\Actions\AbstractYouTubeAction;
use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;

class VideoCommentsAction extends AbstractYouTubeAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(YouTubeCommentsQueryDTO $query): array
    {
        return $this->gateway->videoComments($query->toArray());
    }
}
