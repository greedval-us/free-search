<?php

namespace App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics;

final class SiteIntelAnalyticsHeadersCoverageCalculator
{
    /**
     * @param array<string, mixed> $siteHealth
     * @return array{present: int, total: int, percent: int}
     */
    public function calculate(array $siteHealth): array
    {
        $headers = is_array($siteHealth['headers'] ?? null) ? $siteHealth['headers'] : [];
        $present = 0;

        foreach ($headers as $header) {
            if (($header['present'] ?? false) === true) {
                $present++;
            }
        }

        $total = count($headers);

        return [
            'present' => $present,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($present / $total) * 100) : 0,
        ];
    }
}
