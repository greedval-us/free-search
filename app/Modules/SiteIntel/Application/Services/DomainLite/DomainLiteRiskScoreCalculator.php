<?php

namespace App\Modules\SiteIntel\Application\Services\DomainLite;

use App\Modules\SiteIntel\Enums\SiteIntelScoreLevel;
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
        $breakdown = [];

        $applySignal = function (string $key, int $points) use (&$score, &$signals, &$breakdown): void {
            $score += $points;
            $signals[] = $key;
            $breakdown[] = [
                'key' => $key,
                'points' => $points,
            ];
        };

        if (empty($dns['a']) && empty($dns['aaaa'])) {
            $applySignal('no_a_or_aaaa_records', 25);
        }

        if (empty($dns['ns'])) {
            $applySignal('no_ns_records', 20);
        }

        if (empty($dns['mx'])) {
            $applySignal('no_mx_records', 10);
        }

        if (($dns['emailSecurity']['hasSpf'] ?? false) !== true) {
            $applySignal('missing_spf', 8);
        }

        $spfQualifier = $dns['emailSecurity']['spfPolicy']['allQualifier'] ?? null;
        if ($spfQualifier === '+' || $spfQualifier === '?') {
            $applySignal('weak_spf_policy', 12);
        } elseif ($spfQualifier === '~') {
            $applySignal('soft_spf_policy', 4);
        }

        $spfIncludeCount = (int) ($dns['emailSecurity']['spfPolicy']['includeCount'] ?? 0);
        if ($spfIncludeCount >= 5) {
            $applySignal('spf_too_many_includes', 6);
        }

        if (($dns['emailSecurity']['hasDmarc'] ?? false) !== true) {
            $applySignal('missing_dmarc', 8);
        }

        $dmarcPolicy = $dns['emailSecurity']['dmarcPolicy']['policy'] ?? null;
        if ($dmarcPolicy === 'none') {
            $applySignal('dmarc_monitoring_only', 8);
        }

        $dmarcPct = $dns['emailSecurity']['dmarcPolicy']['percentage'] ?? null;
        if (is_int($dmarcPct) && $dmarcPct > 0 && $dmarcPct < 100) {
            $applySignal('dmarc_partial_enforcement', 4);
        }

        if (($dns['dnssec']['enabled'] ?? false) !== true) {
            $applySignal('dnssec_missing', 4);
        }

        if (($whois['available'] ?? false) !== true) {
            $applySignal('whois_unavailable', 8);
        }

        if (is_string($whois['createdAt'] ?? null)) {
            $createdAt = Carbon::parse($whois['createdAt']);
            $domainAgeDays = $createdAt->diffInDays(Carbon::now(), false);

            if ($domainAgeDays >= 0 && $domainAgeDays <= 30) {
                $applySignal('new_domain_30_days', 28);
            } elseif ($domainAgeDays <= 90) {
                $applySignal('new_domain_90_days', 14);
            }
        }

        if (is_string($whois['expiresAt'] ?? null)) {
            $expiresAt = Carbon::parse($whois['expiresAt']);
            $daysToExpiry = Carbon::now()->diffInDays($expiresAt, false);

            if ($daysToExpiry < 0) {
                $applySignal('domain_expired', 40);
            } elseif ($daysToExpiry <= 30) {
                $applySignal('domain_expires_soon', 20);
            } elseif ($daysToExpiry <= 90) {
                $applySignal('domain_expires_in_90_days', 10);
            }
        }

        $score = max(0, min(100, $score));
        $level = SiteIntelScoreLevel::fromThresholds($score, 60, 30)->value;

        return [
            'score' => $score,
            'level' => $level,
            'signals' => array_values(array_unique($signals)),
            'breakdown' => $breakdown,
        ];
    }
}
