<?php

namespace App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics;

final class SiteIntelAnalyticsOverviewBuilder
{
    /**
     * @param array<string, mixed> $siteHealth
     * @param array<string, mixed> $domainLite
     * @param array{healthScore: int, domainRiskScore: int, overallScore: int} $scores
     * @param array{present: int, total: int, percent: int} $headersCoverage
     * @param array<int, string> $riskSignals
     * @param array<int, string> $strengthSignals
     * @param array<int, string> $recommendations
     * @return array<string, mixed>
     */
    public function build(
        array $siteHealth,
        array $domainLite,
        array $scores,
        array $headersCoverage,
        ?int $daysToDomainExpiry,
        array $riskSignals,
        array $strengthSignals,
        array $recommendations,
    ): array {
        return [
            'score' => [
                'value' => $scores['overallScore'],
                'level' => $this->scoreLevel($scores['overallScore']),
            ],
            'healthScore' => $scores['healthScore'],
            'domainRiskScore' => $scores['domainRiskScore'],
            'headersCoverage' => $headersCoverage,
            'dnsStats' => $this->dnsStats($domainLite),
            'daysToDomainExpiry' => $daysToDomainExpiry,
            'redirects' => (int) ($siteHealth['http']['totalRedirects'] ?? 0),
            'recommendations' => $recommendations,
            'signals' => [
                'risks' => $riskSignals,
                'strengths' => $strengthSignals,
            ],
        ];
    }

    private function scoreLevel(int $score): string
    {
        return match (true) {
            $score >= 75 => 'high',
            $score >= 45 => 'medium',
            default => 'low',
        };
    }

    /**
     * @param array<string, mixed> $domainLite
     * @return array{a: int, aaaa: int, ns: int, mx: int}
     */
    private function dnsStats(array $domainLite): array
    {
        return [
            'a' => count($domainLite['dns']['a'] ?? []),
            'aaaa' => count($domainLite['dns']['aaaa'] ?? []),
            'ns' => count($domainLite['dns']['ns'] ?? []),
            'mx' => count($domainLite['dns']['mx'] ?? []),
        ];
    }
}
