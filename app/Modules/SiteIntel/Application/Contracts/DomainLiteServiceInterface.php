<?php

namespace App\Modules\SiteIntel\Application\Contracts;

use App\Modules\SiteIntel\DTO\Result\DomainLiteResultDTO;

interface DomainLiteServiceInterface
{
    public function lookup(string $domain): DomainLiteResultDTO;
}
