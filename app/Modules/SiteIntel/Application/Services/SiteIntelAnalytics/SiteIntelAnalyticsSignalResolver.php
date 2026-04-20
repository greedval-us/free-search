<?php

namespace App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics;

final class SiteIntelAnalyticsSignalResolver
{
    /**
     * @param array<string, mixed> $siteHealth
     * @param array<string, mixed> $domainLite
     * @return array<int, string>
     */
    public function riskSignals(array $siteHealth, array $domainLite): array
    {
        return array_values(array_unique(array_merge(
            is_array($siteHealth['score']['signals'] ?? null) ? $siteHealth['score']['signals'] : [],
            is_array($domainLite['risk']['signals'] ?? null) ? $domainLite['risk']['signals'] : [],
        )));
    }

    /**
     * @param array<string, mixed> $siteHealth
     * @param array<string, mixed> $domainLite
     * @return array<int, string>
     */
    public function strengthSignals(array $siteHealth, array $domainLite, int $headersCoveragePercent): array
    {
        $signals = [];

        if (str_starts_with((string) ($siteHealth['http']['finalUrl'] ?? ''), 'https://')) {
            $signals[] = 'https_enforced';
        }

        if (($siteHealth['ssl']['available'] ?? false) === true && (($siteHealth['ssl']['daysRemaining'] ?? 0) > 30)) {
            $signals[] = 'ssl_valid';
        }

        if (($domainLite['dns']['emailSecurity']['hasSpf'] ?? false) === true) {
            $signals[] = 'spf_present';
        }

        if (($domainLite['dns']['emailSecurity']['hasDmarc'] ?? false) === true) {
            $signals[] = 'dmarc_present';
        }

        if (($domainLite['whois']['available'] ?? false) === true) {
            $signals[] = 'whois_available';
        }

        if ($headersCoveragePercent >= 70) {
            $signals[] = 'headers_coverage_good';
        }

        return array_values(array_unique($signals));
    }
}
