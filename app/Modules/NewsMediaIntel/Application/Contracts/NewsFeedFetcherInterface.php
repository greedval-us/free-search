<?php

namespace App\Modules\NewsMediaIntel\Application\Contracts;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

interface NewsFeedFetcherInterface
{
    /**
     * @return array<int, NewsMentionDTO>
     */
    public function fetchAll(string $query): array;
}

