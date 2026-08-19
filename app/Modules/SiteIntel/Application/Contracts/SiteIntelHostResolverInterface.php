<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface SiteIntelHostResolverInterface
{
    /**
     * @return list<string>
     */
    public function resolve(string $host): array;
}
