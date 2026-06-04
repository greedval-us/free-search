<?php

namespace App\Modules\SiteIntel\Application\Contracts;

use App\Modules\SiteIntel\DTO\Result\SiteIntelAnalyticsResultDTO;

interface SiteIntelAnalyticsServiceInterface
{
    public function analyze(string $url, string $domain): SiteIntelAnalyticsResultDTO;
}
