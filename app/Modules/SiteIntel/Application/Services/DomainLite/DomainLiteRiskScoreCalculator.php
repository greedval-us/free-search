<?php

namespace App\Modules\SiteIntel\Application\Services\DomainLite;

use App\Modules\SiteIntel\Enums\SiteIntelScoreLevel;
use Carbon\Carbon;

final class DomainLiteRiskScoreCalculator
{
    private const SIGNAL_POINTS = [
        'no_a_or_aaaa_records' => 25,
        'no_ns_records' => 20,
        'no_mx_records' => 10,
        'missing_spf' => 8,
        'weak_spf_policy' => 12,
        'soft_spf_policy' => 4,
        'spf_too_many_includes' => 6,
        'missing_dmarc' => 8,
        'dmarc_monitoring_only' => 8,
        'dmarc_partial_enforcement' => 4,
        'dnssec_missing' => 4,
        'whois_unavailable' => 8,
        'new_domain_30_days' => 28,
        'new_domain_90_days' => 14,
        'domain_expired' => 40,
        'domain_expires_soon' => 20,
        'domain_expires_in_90_days' => 10,
    ];

    private const MAX_SPF_INCLUDES_BEFORE_WARNING = 5;

    private const FULL_DMARC_ENFORCEMENT_PERCENT = 100;

    private const HIGH_RISK_DOMAIN_AGE_DAYS = 30;

    private const MEDIUM_RISK_DOMAIN_AGE_DAYS = 90;

    private const EXPIRY_WARNING_DAYS = 30;

    private const EXPIRY_NOTICE_DAYS = 90;

    private const SCORE_MIN = 0;

    private const SCORE_MAX = 100;

    private const SCORE_HIGH_THRESHOLD = 60;

    private const SCORE_MEDIUM_THRESHOLD = 30;

    /**
     * @param  array<string, mixed>  $dns
     * @param  array<string, mixed>  $whois
     * @return array<string, mixed>
     */
    public function calculate(array $dns, array $whois): array
    {
        $score = 0;
        $signals = [];
        $breakdown = [];

        $applySignal = function (string $key) use (&$score, &$signals, &$breakdown): void {
            $points = self::SIGNAL_POINTS[$key];
            $score += $points;
            $signals[] = $key;
            $breakdown[] = [
                'key' => $key,
                'points' => $points,
            ];
        };

        if (empty($dns['a']) && empty($dns['aaaa'])) {
            $applySignal('no_a_or_aaaa_records');
        }

        if (empty($dns['ns'])) {
            $applySignal('no_ns_records');
        }

        if (empty($dns['mx'])) {
            $applySignal('no_mx_records');
        }

        if (($dns['emailSecurity']['hasSpf'] ?? false) !== true) {
            $applySignal('missing_spf');
        }

        $spfQualifier = $dns['emailSecurity']['spfPolicy']['allQualifier'] ?? null;
        if ($spfQualifier === '+' || $spfQualifier === '?') {
            $applySignal('weak_spf_policy');
        } elseif ($spfQualifier === '~') {
            $applySignal('soft_spf_policy');
        }

        $spfIncludeCount = (int) ($dns['emailSecurity']['spfPolicy']['includeCount'] ?? 0);
        if ($spfIncludeCount >= self::MAX_SPF_INCLUDES_BEFORE_WARNING) {
            $applySignal('spf_too_many_includes');
        }

        if (($dns['emailSecurity']['hasDmarc'] ?? false) !== true) {
            $applySignal('missing_dmarc');
        }

        $dmarcPolicy = $dns['emailSecurity']['dmarcPolicy']['policy'] ?? null;
        if ($dmarcPolicy === 'none') {
            $applySignal('dmarc_monitoring_only');
        }

        $dmarcPct = $dns['emailSecurity']['dmarcPolicy']['percentage'] ?? null;
        if (is_int($dmarcPct) && $dmarcPct > 0 && $dmarcPct < self::FULL_DMARC_ENFORCEMENT_PERCENT) {
            $applySignal('dmarc_partial_enforcement');
        }

        if (($dns['dnssec']['enabled'] ?? false) !== true) {
            $applySignal('dnssec_missing');
        }

        if (($whois['available'] ?? false) !== true) {
            $applySignal('whois_unavailable');
        }

        if (is_string($whois['createdAt'] ?? null)) {
            $createdAt = Carbon::parse($whois['createdAt']);
            $domainAgeDays = $createdAt->diffInDays(Carbon::now(), false);

            if ($domainAgeDays >= 0 && $domainAgeDays <= self::HIGH_RISK_DOMAIN_AGE_DAYS) {
                $applySignal('new_domain_30_days');
            } elseif ($domainAgeDays <= self::MEDIUM_RISK_DOMAIN_AGE_DAYS) {
                $applySignal('new_domain_90_days');
            }
        }

        if (is_string($whois['expiresAt'] ?? null)) {
            $expiresAt = Carbon::parse($whois['expiresAt']);
            $daysToExpiry = Carbon::now()->diffInDays($expiresAt, false);

            if ($daysToExpiry < 0) {
                $applySignal('domain_expired');
            } elseif ($daysToExpiry <= self::EXPIRY_WARNING_DAYS) {
                $applySignal('domain_expires_soon');
            } elseif ($daysToExpiry <= self::EXPIRY_NOTICE_DAYS) {
                $applySignal('domain_expires_in_90_days');
            }
        }

        $score = max(self::SCORE_MIN, min(self::SCORE_MAX, $score));
        $level = SiteIntelScoreLevel::fromThresholds(
            $score,
            self::SCORE_HIGH_THRESHOLD,
            self::SCORE_MEDIUM_THRESHOLD,
        )->value;

        return [
            'score' => $score,
            'level' => $level,
            'signals' => array_values(array_unique($signals)),
            'breakdown' => $breakdown,
        ];
    }
}
