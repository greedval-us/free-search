<?php

namespace App\Modules\DomainInfraIntel\Application\Contracts;

interface DomainRdapClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function lookup(string $domain): array;
}

