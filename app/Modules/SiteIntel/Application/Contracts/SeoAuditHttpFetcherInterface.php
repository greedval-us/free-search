<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface SeoAuditHttpFetcherInterface
{
    /**
     * @return array{url: string,status: int,headers: array<string, mixed>,body: string,responseTimeMs: int,error: string|null}
     */
    public function fetch(string $url): array;
}

