<?php

namespace App\Modules\NewsMediaIntel\Application\Contracts;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMediaIntelResultDTO;

interface NewsMediaIntelServiceInterface
{
    public function monitor(string $query): NewsMediaIntelResultDTO;
}

