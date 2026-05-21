<?php

namespace App\Modules\YouTube\Actions\Request;

use App\Modules\YouTube\Actions\AbstractYouTubeAction;
use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;

class AnalyticsSummaryAction extends AbstractYouTubeAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(YouTubeAnalyticsLookupDTO $query): array
    {
        return $this->gateway->analyticsSummary($query->toArray());
    }
}
