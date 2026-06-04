<?php

namespace App\Modules\SiteIntel\Application\Contracts;

use App\Modules\SiteIntel\DTO\Result\SeoAuditResultDTO;

interface SeoAuditServiceInterface
{
    public function audit(string $url, int $crawlLimit = 8, ?string $platformType = null): SeoAuditResultDTO;
}
