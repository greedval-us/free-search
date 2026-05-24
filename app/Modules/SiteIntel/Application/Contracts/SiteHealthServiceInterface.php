<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface SiteHealthServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function check(string $url): array;
}

