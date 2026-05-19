<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface SiteHealthDnsResolverInterface
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(string $host): array;
}

