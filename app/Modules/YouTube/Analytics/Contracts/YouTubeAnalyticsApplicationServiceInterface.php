<?php

namespace App\Modules\YouTube\Analytics\Contracts;

use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;

interface YouTubeAnalyticsApplicationServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function summary(YouTubeAnalyticsLookupDTO $query): array;
}
