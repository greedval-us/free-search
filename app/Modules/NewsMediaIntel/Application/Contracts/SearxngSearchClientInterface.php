<?php

namespace App\Modules\NewsMediaIntel\Application\Contracts;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchResultDTO;

interface SearxngSearchClientInterface
{
    public function search(string $query, ?NewsSearchOptionsDTO $options = null, ?float $deadline = null): NewsSearchResultDTO;
}
