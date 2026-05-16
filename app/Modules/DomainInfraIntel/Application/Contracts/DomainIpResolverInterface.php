<?php

namespace App\Modules\DomainInfraIntel\Application\Contracts;

interface DomainIpResolverInterface
{
    /**
     * @return array<int, string>
     */
    public function resolve(string $domain): array;
}

