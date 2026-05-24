<?php

namespace App\Modules\CompanyIntel\Application\Services\CompanyIntel;

use App\Modules\CompanyIntel\Application\Support\CompanyIntelConfig;

final class CompanySummaryBuilder
{
    public function __construct(private readonly CompanyIntelConfig $config)
    {
    }

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
                'riskScore' => null,
                'riskExplanation' => 'risk_unknown_missing_domain',
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

        $riskScore = $this->resolveRiskScore($signals);
        $riskLevel = $this->resolveRiskLevel($riskScore);

        return [
            'riskLevel' => $riskLevel,
            'riskScore' => $riskScore,
            'riskExplanation' => $this->resolveRiskExplanation($riskLevel, $signals),
            'signals' => $signals,
            'strengths' => $strengths,
            'recommendations' => array_values(array_unique($recommendations)),
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

        if (count($dns['mx'] ?? []) === 0) {
            $signals[] = 'missing_mx';
            $recommendations[] = 'configure_mx_records';
        } else {
            $strengths[] = 'mx_present';
        }

        if (count($dns['ns'] ?? []) < 2) {
            $signals[] = 'weak_ns_redundancy';
            $recommendations[] = 'improve_dns_redundancy';
        } else {
            $strengths[] = 'ns_redundancy_ok';
        }

        if (count($dns['caa'] ?? []) === 0) {
            $signals[] = 'missing_caa';
            $recommendations[] = 'configure_caa';
        } else {
            $strengths[] = 'caa_present';
        }

        if (($dns['dnssec']['enabled'] ?? false) !== true) {
            $signals[] = 'dnssec_not_enabled';
            $recommendations[] = 'consider_dnssec';
        } else {
            $strengths[] = 'dnssec_enabled';
        }

        if (($dns['emailSecurity']['spfPolicy']['isStrict'] ?? false) !== true && ($dns['emailSecurity']['hasSpf'] ?? false) === true) {
            $signals[] = 'spf_not_strict';
            $recommendations[] = 'tighten_spf_policy';
        } else {
            if (($dns['emailSecurity']['hasSpf'] ?? false) === true) {
                $strengths[] = 'spf_strict';
            }
        }

        $dmarcPolicy = strtolower((string) ($dns['emailSecurity']['dmarcPolicy']['policy'] ?? ''));
        if (($dns['emailSecurity']['hasDmarc'] ?? false) === true && !in_array($dmarcPolicy, ['quarantine', 'reject'], true)) {
            $signals[] = 'dmarc_not_enforced';
            $recommendations[] = 'enforce_dmarc_policy';
        } else {
            if (($dns['emailSecurity']['hasDmarc'] ?? false) === true) {
                $strengths[] = 'dmarc_enforced';
            }
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

        $createdAt = $this->parseDate($whois['createdAt'] ?? null);
        if ($createdAt !== null) {
            $domainAgeDays = $createdAt->diffInDays(now());
            if ($domainAgeDays < 180) {
                $signals[] = 'young_domain';
                $recommendations[] = 'monitor_new_domain_risk';
            } else {
                $strengths[] = 'domain_age_mature';
            }
        }

        $expiresAt = $this->parseDate($whois['expiresAt'] ?? null);
        if ($expiresAt !== null) {
            $daysToExpiry = now()->diffInDays($expiresAt, false);
            if ($daysToExpiry < 0) {
                $signals[] = 'domain_expired';
                $recommendations[] = 'renew_domain_immediately';
            } elseif ($daysToExpiry <= 30) {
                $signals[] = 'domain_expiring_soon';
                $recommendations[] = 'renew_domain_soon';
            } else {
                $strengths[] = 'domain_not_expiring_soon';
            }
        }

        if ($this->containsPrivacyRedaction($whois['sample'] ?? null)) {
            $signals[] = 'whois_privacy_redacted';
            $recommendations[] = 'verify_identity_through_additional_sources';
        }
    }

    /**
     * @param array<int, string> $signals
     */
    private function resolveRiskScore(array $signals): int
    {
        $score = 0;

        foreach (array_values(array_unique($signals)) as $signal) {
            $weight = $this->config->riskWeight($signal, 10);
            $score += max(0, $weight);
        }

        return min(100, $score);
    }

    private function resolveRiskLevel(int $riskScore): string
    {
        $highThreshold = $this->config->riskScoreForHigh();
        $mediumThreshold = $this->config->riskScoreForMedium();

        return match (true) {
            $riskScore >= $highThreshold => 'high',
            $riskScore >= $mediumThreshold => 'medium',
            default => 'low',
        };
    }

    /**
     * @param array<int, string> $signals
     */
    private function resolveRiskExplanation(string $riskLevel, array $signals): string
    {
        if ($signals === []) {
            return 'risk_low_no_critical_signals';
        }

        return match ($riskLevel) {
            'high' => 'risk_high_multiple_critical_signals',
            'medium' => 'risk_medium_several_signals',
            'low' => 'risk_low_minor_signals',
            default => 'risk_unknown_missing_domain',
        };
    }

    private function parseDate(mixed $value): ?\Carbon\Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function containsPrivacyRedaction(mixed $sample): bool
    {
        if (!is_string($sample) || trim($sample) === '') {
            return false;
        }

        $needles = [
            'redacted for privacy',
            'privacy service',
            'whois privacy',
            'data protected',
            'privacyguardian',
        ];
        $text = strtolower($sample);

        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
