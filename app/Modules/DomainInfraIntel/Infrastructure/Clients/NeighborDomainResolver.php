<?php

namespace App\Modules\DomainInfraIntel\Infrastructure\Clients;

use App\Modules\DomainInfraIntel\Application\Contracts\NeighborDomainResolverInterface;

final class NeighborDomainResolver implements NeighborDomainResolverInterface
{
    public function resolve(array $ips): array
    {
        $neighbors = [];

        foreach ($ips as $ip) {
            $host = @gethostbyaddr((string) $ip);
            if (is_string($host) && $host !== '' && $host !== $ip) {
                $neighbors[] = $host;
            }
        }

        return array_values(array_unique(array_filter($neighbors)));
    }
}

