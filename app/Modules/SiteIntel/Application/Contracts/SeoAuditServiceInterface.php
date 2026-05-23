<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface SeoAuditServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function audit(string $url, int $crawlLimit = 8, ?string $platformType = null): array;
}

