<?php

namespace App\Modules\CompanyIntel\Application\Services\CompanyIntel;

final class CompanySummaryBuilder
{
    /**
     * @param array<string, mixed>|null $dns
     * @param array<string, mixed>|null $whois
     * @return array<string, mixed>
     */
    public function build(?array $dns, ?array $whois): array
    {
        if ($dns === null || $whois === null) {
            return [
                'riskLevel' => 'unknown',
                'signals' => ['domain_not_detected'],
                'strengths' => [],
                'recommendations' => ['specify_official_domain'],
            ];
        }

        $signals = [];
        $strengths = [];
        $recommendations = [];

        $this->collectDnsSignals($dns, $signals, $strengths, $recommendations);
        $this->collectWhoisSignals($whois, $signals, $strengths, $recommendations);

        if ($recommendations === []) {
            $recommendations[] = 'maintain_monitoring';
        }

        return [
            'riskLevel' => $this->resolveRiskLevel(count($signals)),
            'signals' => $signals,
            'strengths' => $strengths,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @param array<string, mixed> $dns
     * @param array<int, string> $signals
     * @param array<int, string> $strengths
     * @param array<int, string> $recommendations
     */
    private function collectDnsSignals(array $dns, array &$signals, array &$strengths, array &$recommendations): void
    {
        if (empty($dns['a']) && empty($dns['aaaa'])) {
            $signals[] = 'no_dns_resolution';
            $recommendations[] = 'verify_company_domain_or_dns';
        } else {
            $strengths[] = 'dns_resolution_ok';
        }

        if (($dns['emailSecurity']['hasSpf'] ?? false) !== true) {
            $signals[] = 'missing_spf';
            $recommendations[] = 'configure_spf';
        } else {
            $strengths[] = 'spf_present';
        }

        if (($dns['emailSecurity']['hasDmarc'] ?? false) !== true) {
            $signals[] = 'missing_dmarc';
            $recommendations[] = 'configure_dmarc';
        } else {
            $strengths[] = 'dmarc_present';
        }
    }

    /**
     * @param array<string, mixed> $whois
     * @param array<int, string> $signals
     * @param array<int, string> $strengths
     * @param array<int, string> $recommendations
     */
    private function collectWhoisSignals(array $whois, array &$signals, array &$strengths, array &$recommendations): void
    {
        if (($whois['available'] ?? false) !== true) {
            $signals[] = 'whois_unavailable';
            $recommendations[] = 'check_whois_visibility';
        } else {
            $strengths[] = 'whois_available';
        }
    }

    private function resolveRiskLevel(int $signalCount): string
    {
        $highThreshold = (int) config('osint.company_intel.risk.signals_for_high', 3);
        $mediumThreshold = (int) config('osint.company_intel.risk.signals_for_medium', 1);

        return match (true) {
            $signalCount >= $highThreshold => 'high',
            $signalCount >= $mediumThreshold => 'medium',
            default => 'low',
        };
    }
}
