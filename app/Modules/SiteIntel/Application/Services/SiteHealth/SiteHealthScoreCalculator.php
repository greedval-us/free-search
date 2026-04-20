<?php

namespace App\Modules\SiteIntel\Application\Services\SiteHealth;

final class SiteHealthScoreCalculator
{
    /**
     * @param array<string, mixed> $dns
     * @param array<string, mixed> $http
     * @param array<string, mixed> $ssl
     * @param array<string, array<string, mixed>> $securityHeaders
     * @return array<string, mixed>
     */
    public function calculate(array $dns, array $http, array $ssl, array $securityHeaders): array
    {
        $score = 100;
        $signals = [];

        if (empty($dns['a']) && empty($dns['aaaa'])) {
            $score -= 25;
            $signals[] = 'no_dns_records';
        }

        $finalStatus = (int) ($http['finalStatus'] ?? 0);
        if ($finalStatus === 0) {
            $score -= 35;
            $signals[] = 'unreachable';
        } elseif ($finalStatus >= 400) {
            $score -= 25;
            $signals[] = 'http_error_status';
        }

        if (str_starts_with((string) ($http['finalUrl'] ?? ''), 'http://')) {
            $score -= 20;
            $signals[] = 'final_url_not_https';
        }

        $totalRedirects = (int) ($http['totalRedirects'] ?? 0);
        if ($totalRedirects > 3) {
            $score -= 10;
            $signals[] = 'too_many_redirects';
        }

        if (($ssl['available'] ?? false) !== true && str_starts_with((string) ($http['finalUrl'] ?? ''), 'https://')) {
            $score -= 20;
            $signals[] = 'ssl_unavailable';
        }

        $daysRemaining = $ssl['daysRemaining'] ?? null;
        if (is_int($daysRemaining)) {
            if ($daysRemaining < 0) {
                $score -= 35;
                $signals[] = 'ssl_expired';
            } elseif ($daysRemaining <= 30) {
                $score -= 15;
                $signals[] = 'ssl_expiring_soon';
            }
        }

        foreach ($securityHeaders as $headerName => $headerInfo) {
            if (($headerInfo['present'] ?? false) !== true) {
                $score -= 6;
                $signals[] = 'missing_' . str_replace('-', '_', $headerName);
            }
        }

        $score = max(0, min(100, $score));
        $level = match (true) {
            $score >= 80 => 'high',
            $score >= 55 => 'medium',
            default => 'low',
        };

        return [
            'value' => $score,
            'level' => $level,
            'signals' => array_values(array_unique($signals)),
        ];
    }
}

