<?php

namespace App\Modules\SiteIntel\Application\Services\DomainLite;

use Carbon\Carbon;

final class DomainLiteRiskScoreCalculator
{
    /**
     * @param array<string, mixed> $dns
     * @param array<string, mixed> $whois
     * @return array<string, mixed>
     */
    public function calculate(array $dns, array $whois): array
    {
        $score = 0;
        $signals = [];

        if (empty($dns['a']) && empty($dns['aaaa'])) {
            $score += 25;
            $signals[] = 'no_a_or_aaaa_records';
        }

        if (empty($dns['ns'])) {
            $score += 20;
            $signals[] = 'no_ns_records';
        }

        if (empty($dns['mx'])) {
            $score += 10;
            $signals[] = 'no_mx_records';
        }

        if (($dns['emailSecurity']['hasSpf'] ?? false) !== true) {
            $score += 8;
            $signals[] = 'missing_spf';
        }

        $spfQualifier = $dns['emailSecurity']['spfPolicy']['allQualifier'] ?? null;
        if ($spfQualifier === '+' || $spfQualifier === '?') {
            $score += 12;
            $signals[] = 'weak_spf_policy';
        } elseif ($spfQualifier === '~') {
            $score += 4;
            $signals[] = 'soft_spf_policy';
        }

        $spfIncludeCount = (int) ($dns['emailSecurity']['spfPolicy']['includeCount'] ?? 0);
        if ($spfIncludeCount >= 5) {
            $score += 6;
            $signals[] = 'spf_too_many_includes';
        }

        if (($dns['emailSecurity']['hasDmarc'] ?? false) !== true) {
            $score += 8;
            $signals[] = 'missing_dmarc';
        }

        $dmarcPolicy = $dns['emailSecurity']['dmarcPolicy']['policy'] ?? null;
        if ($dmarcPolicy === 'none') {
            $score += 8;
            $signals[] = 'dmarc_monitoring_only';
        }

        $dmarcPct = $dns['emailSecurity']['dmarcPolicy']['percentage'] ?? null;
        if (is_int($dmarcPct) && $dmarcPct > 0 && $dmarcPct < 100) {
            $score += 4;
            $signals[] = 'dmarc_partial_enforcement';
        }

        if (($dns['dnssec']['enabled'] ?? false) !== true) {
            $score += 4;
            $signals[] = 'dnssec_missing';
        }

        if (($whois['available'] ?? false) !== true) {
            $score += 8;
            $signals[] = 'whois_unavailable';
        }

        if (is_string($whois['createdAt'] ?? null)) {
            $createdAt = Carbon::parse($whois['createdAt']);
            $domainAgeDays = $createdAt->diffInDays(Carbon::now(), false);

            if ($domainAgeDays >= 0 && $domainAgeDays <= 30) {
                $score += 28;
                $signals[] = 'new_domain_30_days';
            } elseif ($domainAgeDays <= 90) {
                $score += 14;
                $signals[] = 'new_domain_90_days';
            }
        }

        if (is_string($whois['expiresAt'] ?? null)) {
            $expiresAt = Carbon::parse($whois['expiresAt']);
            $daysToExpiry = Carbon::now()->diffInDays($expiresAt, false);

            if ($daysToExpiry < 0) {
                $score += 40;
                $signals[] = 'domain_expired';
            } elseif ($daysToExpiry <= 30) {
                $score += 20;
                $signals[] = 'domain_expires_soon';
            } elseif ($daysToExpiry <= 90) {
                $score += 10;
                $signals[] = 'domain_expires_in_90_days';
            }
        }

        $score = max(0, min(100, $score));
        $level = match (true) {
            $score >= 60 => 'high',
            $score >= 30 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
            'signals' => array_values(array_unique($signals)),
        ];
    }
}
