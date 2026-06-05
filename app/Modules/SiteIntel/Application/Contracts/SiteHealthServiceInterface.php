<?php

namespace App\Modules\SiteIntel\Application\Contracts;

use App\Modules\SiteIntel\DTO\Result\SiteHealthResultDTO;

interface SiteHealthServiceInterface
{
    public function check(string $url): SiteHealthResultDTO;
}
