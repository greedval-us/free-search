<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface DomainLiteDnsResolverInterface
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(string $domain): array;
}

