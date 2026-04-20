<?php

namespace App\Modules\SiteIntel\Application\Services\SiteIntelAnalytics;

use Carbon\Carbon;

final class SiteIntelAnalyticsDomainExpiryCalculator
{
    /**
     * @param array<string, mixed> $domainLite
     */
    public function calculateDaysToExpiry(array $domainLite): ?int
    {
        $expiresAt = $domainLite['whois']['expiresAt'] ?? null;
        if (!is_string($expiresAt) || trim($expiresAt) === '') {
            return null;
        }

        return Carbon::now()->diffInDays(Carbon::parse($expiresAt), false);
    }
}
