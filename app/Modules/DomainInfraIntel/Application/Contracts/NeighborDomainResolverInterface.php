<?php

namespace App\Modules\DomainInfraIntel\Application\Contracts;

interface NeighborDomainResolverInterface
{
    /**
     * @param array<int, string> $ips
     * @return array<int, string>
     */
    public function resolve(array $ips): array;
}

