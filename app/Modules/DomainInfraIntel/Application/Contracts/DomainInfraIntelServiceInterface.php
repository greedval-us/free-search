<?php

namespace App\Modules\DomainInfraIntel\Application\Contracts;

use App\Modules\DomainInfraIntel\Domain\DTO\DomainInfraIntelResultDTO;

interface DomainInfraIntelServiceInterface
{
    public function inspect(string $domain): DomainInfraIntelResultDTO;
}

