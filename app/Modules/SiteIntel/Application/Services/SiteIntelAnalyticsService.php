<?php

namespace App\Modules\SiteIntel\Application\Services;

use App\Modules\SiteIntel\Application\Contracts\SiteIntelAnalyticsServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthServiceInterface;
use App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics\SiteIntelAnalyticsDomainExpiryCalculator;
use App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics\SiteIntelAnalyticsHeadersCoverageCalculator;
use App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics\SiteIntelAnalyticsOverviewBuilder;
use App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics\SiteIntelAnalyticsRecommendationBuilder;
use App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics\SiteIntelAnalyticsScoreCalculator;
use App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics\SiteIntelAnalyticsSignalResolver;
use App\Modules\SiteIntel\DTO\Result\SiteIntelAnalyticsResultDTO;
use Carbon\Carbon;

final class SiteIntelAnalyticsService implements SiteIntelAnalyticsServiceInterface
{
    public function __construct(
        private readonly SiteHealthServiceInterface $siteHealthService,
        private readonly DomainLiteServiceInterface $domainLiteService,
        private readonly SiteIntelAnalyticsScoreCalculator $scoreCalculator,
        private readonly SiteIntelAnalyticsHeadersCoverageCalculator $headersCoverageCalculator,
        private readonly SiteIntelAnalyticsDomainExpiryCalculator $domainExpiryCalculator,
        private readonly SiteIntelAnalyticsSignalResolver $signalResolver,
        private readonly SiteIntelAnalyticsRecommendationBuilder $recommendationBuilder,
        private readonly SiteIntelAnalyticsOverviewBuilder $overviewBuilder,
    ) {
    }

    public function analyze(string $url, string $domain): SiteIntelAnalyticsResultDTO
    {
        $siteHealth = $this->siteHealthService->check($url)->toArray();
        $domainLite = $this->domainLiteService->lookup($domain)->toArray();

        $scores = $this->scoreCalculator->calculate($siteHealth, $domainLite);
        $headersCoverage = $this->headersCoverageCalculator->calculate($siteHealth);
        $daysToDomainExpiry = $this->domainExpiryCalculator->calculateDaysToExpiry($domainLite);
        $riskSignals = $this->signalResolver->riskSignals($siteHealth, $domainLite);
        $strengthSignals = $this->signalResolver->strengthSignals($siteHealth, $domainLite, $headersCoverage['percent']);
        $recommendations = $this->recommendationBuilder->build($riskSignals, $headersCoverage['percent'], $daysToDomainExpiry);

        return new SiteIntelAnalyticsResultDTO([
            'target' => [
                'url' => $url,
                'domain' => $domain,
            ],
            'checkedAt' => Carbon::now()->toIso8601String(),
            'overview' => $this->overviewBuilder->build(
                $siteHealth,
                $domainLite,
                $scores,
                $headersCoverage,
                $daysToDomainExpiry,
                $riskSignals,
                $strengthSignals,
                $recommendations,
            ),
            'siteHealth' => $siteHealth,
            'domainLite' => $domainLite,
        ]);
    }
}
