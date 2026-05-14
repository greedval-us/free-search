<?php

namespace App\Modules\CompanyIntel\Application\Services\CompanyIntel;

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
                'hasSpf' => (bool) ($dns['emailSecurity']['hasSpf'] ?? false),
                'hasDmarc' => (bool) ($dns['emailSecurity']['hasDmarc'] ?? false),
            ],
            'whois' => [
                'available' => (bool) ($whois['available'] ?? false),
                'registrar' => $whois['registrar'] ?? null,
                'country' => $whois['country'] ?? null,
                'createdAt' => $whois['createdAt'] ?? null,
                'expiresAt' => $whois['expiresAt'] ?? null,
            ],
        ];
    }
}
