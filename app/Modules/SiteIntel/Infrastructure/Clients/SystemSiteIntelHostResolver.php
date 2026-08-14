<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Modules\SiteIntel\Application\Contracts\SiteIntelHostResolverInterface;

final class SystemSiteIntelHostResolver implements SiteIntelHostResolverInterface
{
    public function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = $this->resolveIpv4($host);

        if (defined('DNS_AAAA')) {
            $addresses = [...$addresses, ...$this->resolveIpv6($host)];
        }

        return array_values(array_unique($addresses));
    }

    /**
     * @return list<string>
     */
    private function resolveIpv4(string $host): array
    {
        return array_values(array_filter(
            gethostbynamel($host) ?: [],
            static fn (mixed $ip): bool => is_string($ip) && $ip !== '',
        ));
    }

    /**
     * @return list<string>
     */
    private function resolveIpv6(string $host): array
    {
        $records = @dns_get_record($host, DNS_AAAA);
        if (! is_array($records)) {
            return [];
        }

        $addresses = [];

        foreach ($records as $record) {
            $ip = $record['ipv6'] ?? null;

            if (is_string($ip) && $ip !== '') {
                $addresses[] = $ip;
            }
        }

        return $addresses;
    }
}
