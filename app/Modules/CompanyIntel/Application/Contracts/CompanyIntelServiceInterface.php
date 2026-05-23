<?php

namespace App\Modules\CompanyIntel\Application\Contracts;

use App\Modules\CompanyIntel\Domain\DTO\CompanyIntelResultDTO;

interface CompanyIntelServiceInterface
{
    public function lookup(string $query, ?string $normalizedDomain): CompanyIntelResultDTO;
}

