<?php

namespace App\Modules\DomainInfraIntel\Application\Contracts;

interface AsnLookupClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function lookup(?string $ip): array;
}

