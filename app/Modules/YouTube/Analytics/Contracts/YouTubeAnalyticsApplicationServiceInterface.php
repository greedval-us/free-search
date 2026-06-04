<?php

namespace App\Modules\YouTube\Analytics\Contracts;

use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;
use App\Modules\YouTube\DTO\Result\YouTubeAnalyticsResultDTO;

interface YouTubeAnalyticsApplicationServiceInterface
{
    public function summary(YouTubeAnalyticsLookupDTO $query): YouTubeAnalyticsResultDTO;
}
