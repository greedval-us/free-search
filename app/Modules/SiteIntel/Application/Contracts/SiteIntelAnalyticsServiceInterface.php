<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface SiteIntelAnalyticsServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(string $url, string $domain): array;
}

