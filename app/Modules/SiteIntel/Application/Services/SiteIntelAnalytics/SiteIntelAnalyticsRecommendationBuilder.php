<?php

namespace App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics;

final class SiteIntelAnalyticsRecommendationBuilder
{
    /**
     * @param array<int, string> $riskSignals
     * @return array<int, string>
     */
    public function build(array $riskSignals, int $headersCoveragePercent, ?int $daysToDomainExpiry): array
    {
        $recommendations = [];

        if ($headersCoveragePercent < 70) {
            $recommendations[] = 'improve_security_headers';
        }

        if (in_array('final_url_not_https', $riskSignals, true)) {
            $recommendations[] = 'enforce_https';
        }

        if (in_array('ssl_expired', $riskSignals, true) || in_array('ssl_expiring_soon', $riskSignals, true)) {
            $recommendations[] = 'renew_ssl_certificate';
        }

        if (in_array('missing_spf', $riskSignals, true) || in_array('missing_dmarc', $riskSignals, true)) {
            $recommendations[] = 'configure_email_security';
        }

        if (in_array('no_ns_records', $riskSignals, true) || in_array('no_a_or_aaaa_records', $riskSignals, true)) {
            $recommendations[] = 'review_dns_configuration';
        }

        if ($daysToDomainExpiry !== null && $daysToDomainExpiry <= 90) {
            $recommendations[] = 'renew_domain_early';
        }

        if (in_array('whois_unavailable', $riskSignals, true)) {
            $recommendations[] = 'check_whois_visibility';
        }

        if ($recommendations === []) {
            $recommendations[] = 'maintain_current_posture';
        }

        return array_values(array_unique($recommendations));
    }
}
