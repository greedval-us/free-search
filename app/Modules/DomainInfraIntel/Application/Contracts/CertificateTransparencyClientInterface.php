<?php

namespace App\Modules\DomainInfraIntel\Application\Contracts;

interface CertificateTransparencyClientInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function lookup(string $domain): array;
}

