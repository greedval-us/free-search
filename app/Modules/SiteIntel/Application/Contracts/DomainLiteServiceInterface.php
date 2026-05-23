<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface DomainLiteServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function lookup(string $domain): array;
}

