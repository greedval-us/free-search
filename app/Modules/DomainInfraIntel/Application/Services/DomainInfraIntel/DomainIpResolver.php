<?php

namespace App\Modules\DomainInfraIntel\Application\Services\DomainInfraIntel;

use App\Modules\DomainInfraIntel\Application\Contracts\DomainIpResolverInterface;

final class DomainIpResolver implements DomainIpResolverInterface
{
    public function resolve(string $domain): array
    {
        $records = dns_get_record($domain, DNS_A) ?: [];
        $ips = [];

        foreach ($records as $record) {
            $ip = (string) ($record['ip'] ?? '');
            if ($ip !== '') {
                $ips[] = $ip;
            }
        }

        return array_values(array_unique($ips));
    }
}

