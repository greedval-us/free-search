<?php

namespace App\Modules\NewsMediaIntel\Application\Contracts;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

interface NewsFeedProviderInterface
{
    /**
     * @return array<int, NewsMentionDTO>
     */
    public function fetch(string $query): array;
}

