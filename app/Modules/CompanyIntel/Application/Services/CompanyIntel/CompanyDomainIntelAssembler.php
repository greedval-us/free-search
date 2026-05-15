<?php

namespace App\Modules\CompanyIntel\Application\Services\CompanyIntel;

use Carbon\Carbon;

final class CompanyDomainIntelAssembler
{
    /**
     * @param array<string, mixed>|null $dns
     * @param array<string, mixed>|null $whois
     * @return array<string, mixed>
     */
    public function assemble(?array $dns, ?array $whois): array
    {
        if ($dns === null || $whois === null) {
            return [
                'available' => false,
            ];
        }

        return [
            'available' => true,
            'dns' => [
                'aCount' => count($dns['a'] ?? []),
                'mxCount' => count($dns['mx'] ?? []),
                'nsCount' => count($dns['ns'] ?? []),
                'txtCount' => count($dns['txt'] ?? []),
                'caaCount' => count($dns['caa'] ?? []),
                'dnssecEnabled' => (bool) ($dns['dnssec']['enabled'] ?? false),
                'hasSpf' => (bool) ($dns['emailSecurity']['hasSpf'] ?? false),
                'spfStrict' => (bool) ($dns['emailSecurity']['spfPolicy']['isStrict'] ?? false),
                'hasDmarc' => (bool) ($dns['emailSecurity']['hasDmarc'] ?? false),
                'dmarcPolicy' => $dns['emailSecurity']['dmarcPolicy']['policy'] ?? null,
            ],
            'whois' => [
                'available' => (bool) ($whois['available'] ?? false),
                'registrar' => $whois['registrar'] ?? null,
                'country' => $whois['country'] ?? null,
                'createdAt' => $whois['createdAt'] ?? null,
                'expiresAt' => $whois['expiresAt'] ?? null,
                'domainAgeDays' => $this->resolveDomainAgeDays($whois['createdAt'] ?? null),
                'daysToExpiry' => $this->resolveDaysToExpiry($whois['expiresAt'] ?? null),
            ],
        ];
    }

    private function resolveDomainAgeDays(mixed $createdAt): ?int
    {
        if (!is_string($createdAt) || trim($createdAt) === '') {
            return null;
        }

        try {
            return Carbon::parse($createdAt)->diffInDays(now());
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveDaysToExpiry(mixed $expiresAt): ?int
    {
        if (!is_string($expiresAt) || trim($expiresAt) === '') {
            return null;
        }

        try {
            return now()->diffInDays(Carbon::parse($expiresAt), false);
        } catch (\Throwable) {
            return null;
        }
    }
}
