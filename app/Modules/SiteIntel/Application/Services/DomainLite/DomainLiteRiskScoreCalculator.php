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

        if (($dns['emailSecurity']['hasDmarc'] ?? false) !== true) {
            $score += 8;
            $signals[] = 'missing_dmarc';
        }

        if (($whois['available'] ?? false) !== true) {
            $score += 8;
            $signals[] = 'whois_unavailable';
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

