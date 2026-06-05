<?php

namespace App\Modules\YouTube\Analytics;

use App\Modules\YouTube\Actions\Request\AnalyticsSummaryAction;
use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;
use App\Modules\YouTube\DTO\Result\YouTubeAnalyticsResultDTO;

class YouTubeAnalyticsApplicationService implements YouTubeAnalyticsApplicationServiceInterface
{
    public function __construct(private readonly AnalyticsSummaryAction $analyticsSummaryAction) {}

    public function summary(YouTubeAnalyticsLookupDTO $query): YouTubeAnalyticsResultDTO
    {
        return $this->analyticsSummaryAction->handle($query);
    }
}
