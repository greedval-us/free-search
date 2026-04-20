<?php

namespace App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics;

final class SiteIntelAnalyticsScoreCalculator
{
    /**
     * @param array<string, mixed> $siteHealth
     * @param array<string, mixed> $domainLite
     * @return array{healthScore: int, domainRiskScore: int, overallScore: int}
     */
    public function calculate(array $siteHealth, array $domainLite): array
    {
        $healthScore = (int) ($siteHealth['score']['value'] ?? 0);
        $domainRiskScore = (int) ($domainLite['risk']['score'] ?? 0);

        return [
            'healthScore' => $healthScore,
            'domainRiskScore' => $domainRiskScore,
            'overallScore' => (int) round(($healthScore + (100 - $domainRiskScore)) / 2),
        ];
    }
}
