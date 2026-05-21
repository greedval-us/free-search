<?php

namespace App\Modules\YouTube\Analytics;

use App\Modules\YouTube\Actions\Request\AnalyticsSummaryAction;
use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;

class YouTubeAnalyticsApplicationService implements YouTubeAnalyticsApplicationServiceInterface
{
    public function __construct(private readonly AnalyticsSummaryAction $analyticsSummaryAction) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(YouTubeAnalyticsLookupDTO $query): array
    {
        return $this->analyticsSummaryAction->handle($query);
    }
}
