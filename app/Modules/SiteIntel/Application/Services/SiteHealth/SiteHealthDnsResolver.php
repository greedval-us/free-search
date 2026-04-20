<?php

namespace App\Modules\SiteIntel\Application\Services\SiteHealth;

final class SiteHealthDnsResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(string $host): array
    {
        $a = gethostbynamel($host) ?: [];
        $aaaaRaw = defined('DNS_AAAA') ? @dns_get_record($host, DNS_AAAA) : [];
        $aaaa = [];

        if (is_array($aaaaRaw)) {
            foreach ($aaaaRaw as $record) {
                if (isset($record['ipv6'])) {
                    $aaaa[] = (string) $record['ipv6'];
                }
            }
        }

        return [
            'a' => array_values(array_unique($a)),
            'aaaa' => array_values(array_unique($aaaa)),
        ];
    }
}

