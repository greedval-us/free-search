<?php

namespace App\Modules\SiteIntel\Application\Services\SiteHealth;

use App\Modules\SiteIntel\Enums\SiteIntelScoreLevel;

final class SiteHealthScoreCalculator
{
    private const SIGNAL_PENALTIES = [
        'no_dns_records' => 25,
        'unreachable' => 35,
        'http_error_status' => 25,
        'final_url_not_https' => 20,
        'too_many_redirects' => 10,
        'ssl_unavailable' => 20,
        'ssl_expired' => 35,
        'ssl_expiring_soon' => 15,
    ];

    private const MISSING_SECURITY_HEADER_PENALTY = 6;

    private const MAX_REDIRECTS_BEFORE_WARNING = 3;

    private const SSL_EXPIRY_WARNING_DAYS = 30;

    private const SCORE_MIN = 0;

    private const SCORE_MAX = 100;

    private const SCORE_HIGH_THRESHOLD = 80;

    private const SCORE_MEDIUM_THRESHOLD = 55;

    /**
     * @param  array<string, mixed>  $dns
     * @param  array<string, mixed>  $http
     * @param  array<string, mixed>  $ssl
     * @param  array<string, array<string, mixed>>  $securityHeaders
     * @return array<string, mixed>
     */
    public function calculate(array $dns, array $http, array $ssl, array $securityHeaders): array
    {
        $score = self::SCORE_MAX;
        $signals = [];
        $applySignal = function (string $key, ?int $penalty = null) use (&$score, &$signals): void {
            $score -= $penalty ?? self::SIGNAL_PENALTIES[$key];
            $signals[] = $key;
        };

        if (empty($dns['a']) && empty($dns['aaaa'])) {
            $applySignal('no_dns_records');
        }

        $finalStatus = (int) ($http['finalStatus'] ?? 0);
        if ($finalStatus === 0) {
            $applySignal('unreachable');
        } elseif ($finalStatus >= 400) {
            $applySignal('http_error_status');
        }

        if (str_starts_with((string) ($http['finalUrl'] ?? ''), 'http://')) {
            $applySignal('final_url_not_https');
        }

        $totalRedirects = (int) ($http['totalRedirects'] ?? 0);
        if ($totalRedirects > self::MAX_REDIRECTS_BEFORE_WARNING) {
            $applySignal('too_many_redirects');
        }

        if (($ssl['available'] ?? false) !== true && str_starts_with((string) ($http['finalUrl'] ?? ''), 'https://')) {
            $applySignal('ssl_unavailable');
        }

        $daysRemaining = $ssl['daysRemaining'] ?? null;
        if (is_int($daysRemaining)) {
            if ($daysRemaining < 0) {
                $applySignal('ssl_expired');
            } elseif ($daysRemaining <= self::SSL_EXPIRY_WARNING_DAYS) {
                $applySignal('ssl_expiring_soon');
            }
        }

        foreach ($securityHeaders as $headerName => $headerInfo) {
            if (($headerInfo['present'] ?? false) !== true) {
                $applySignal(
                    'missing_'.str_replace('-', '_', $headerName),
                    self::MISSING_SECURITY_HEADER_PENALTY,
                );
            }
        }

        $score = max(self::SCORE_MIN, min(self::SCORE_MAX, $score));
        $level = SiteIntelScoreLevel::fromThresholds(
            $score,
            self::SCORE_HIGH_THRESHOLD,
            self::SCORE_MEDIUM_THRESHOLD,
        )->value;

        return [
            'value' => $score,
            'level' => $level,
            'signals' => array_values(array_unique($signals)),
        ];
    }
}
