<?php

namespace App\Modules\SiteIntel\Application\Services;

use Carbon\Carbon;

final class SiteIntelAnalyticsService
{
    public function __construct(
        private readonly SiteHealthService $siteHealthService,
        private readonly DomainLiteService $domainLiteService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $url, string $domain): array
    {
        $siteHealth = $this->siteHealthService->check($url);
        $domainLite = $this->domainLiteService->lookup($domain);

        $healthScore = (int) ($siteHealth['score']['value'] ?? 0);
        $domainRiskScore = (int) ($domainLite['risk']['score'] ?? 0);
        $overallScore = (int) round(($healthScore + (100 - $domainRiskScore)) / 2);

        $headers = is_array($siteHealth['headers'] ?? null) ? $siteHealth['headers'] : [];
        $presentHeaders = 0;
        foreach ($headers as $header) {
            if (($header['present'] ?? false) === true) {
                $presentHeaders++;
            }
        }
        $headersTotal = count($headers);
        $headersCoveragePercent = $headersTotal > 0 ? (int) round(($presentHeaders / $headersTotal) * 100) : 0;

        $expiresAt = $domainLite['whois']['expiresAt'] ?? null;
        $daysToDomainExpiry = null;
        if (is_string($expiresAt) && trim($expiresAt) !== '') {
            $daysToDomainExpiry = Carbon::now()->diffInDays(Carbon::parse($expiresAt), false);
        }

        $riskSignals = array_values(array_unique(array_merge(
            is_array($siteHealth['score']['signals'] ?? null) ? $siteHealth['score']['signals'] : [],
            is_array($domainLite['risk']['signals'] ?? null) ? $domainLite['risk']['signals'] : [],
        )));

        $strengthSignals = [];
        if (str_starts_with((string) ($siteHealth['http']['finalUrl'] ?? ''), 'https://')) {
            $strengthSignals[] = 'https_enforced';
        }
        if (($siteHealth['ssl']['available'] ?? false) === true && (($siteHealth['ssl']['daysRemaining'] ?? 0) > 30)) {
            $strengthSignals[] = 'ssl_valid';
        }
        if (($domainLite['dns']['emailSecurity']['hasSpf'] ?? false) === true) {
            $strengthSignals[] = 'spf_present';
        }
        if (($domainLite['dns']['emailSecurity']['hasDmarc'] ?? false) === true) {
            $strengthSignals[] = 'dmarc_present';
        }
        if (($domainLite['whois']['available'] ?? false) === true) {
            $strengthSignals[] = 'whois_available';
        }
        if ($headersCoveragePercent >= 70) {
            $strengthSignals[] = 'headers_coverage_good';
        }

        $recommendations = $this->recommendations($riskSignals, $headersCoveragePercent, $daysToDomainExpiry);

        return [
            'target' => [
                'url' => $url,
                'domain' => $domain,
            ],
            'checkedAt' => Carbon::now()->toIso8601String(),
            'overview' => [
                'score' => [
                    'value' => $overallScore,
                    'level' => $this->scoreLevel($overallScore),
                ],
                'healthScore' => $healthScore,
                'domainRiskScore' => $domainRiskScore,
                'headersCoverage' => [
                    'present' => $presentHeaders,
                    'total' => $headersTotal,
                    'percent' => $headersCoveragePercent,
                ],
                'dnsStats' => [
                    'a' => count($domainLite['dns']['a'] ?? []),
                    'aaaa' => count($domainLite['dns']['aaaa'] ?? []),
                    'ns' => count($domainLite['dns']['ns'] ?? []),
                    'mx' => count($domainLite['dns']['mx'] ?? []),
                ],
                'daysToDomainExpiry' => $daysToDomainExpiry,
                'redirects' => (int) ($siteHealth['http']['totalRedirects'] ?? 0),
                'recommendations' => $recommendations,
                'signals' => [
                    'risks' => $riskSignals,
                    'strengths' => array_values(array_unique($strengthSignals)),
                ],
            ],
            'siteHealth' => $siteHealth,
            'domainLite' => $domainLite,
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
     * @param array<int, string> $riskSignals
     * @return array<int, string>
     */
    private function recommendations(array $riskSignals, int $headersCoveragePercent, ?int $daysToDomainExpiry): array
    {
        $result = [];

        if ($headersCoveragePercent < 70) {
            $result[] = 'improve_security_headers';
        }
        if (in_array('final_url_not_https', $riskSignals, true)) {
            $result[] = 'enforce_https';
        }
        if (in_array('ssl_expired', $riskSignals, true) || in_array('ssl_expiring_soon', $riskSignals, true)) {
            $result[] = 'renew_ssl_certificate';
        }
        if (in_array('missing_spf', $riskSignals, true) || in_array('missing_dmarc', $riskSignals, true)) {
            $result[] = 'configure_email_security';
        }
        if (in_array('no_ns_records', $riskSignals, true) || in_array('no_a_or_aaaa_records', $riskSignals, true)) {
            $result[] = 'review_dns_configuration';
        }
        if ($daysToDomainExpiry !== null && $daysToDomainExpiry <= 90) {
            $result[] = 'renew_domain_early';
        }
        if (in_array('whois_unavailable', $riskSignals, true)) {
            $result[] = 'check_whois_visibility';
        }

        if ($result === []) {
            $result[] = 'maintain_current_posture';
        }

        return array_values(array_unique($result));
    }
}

