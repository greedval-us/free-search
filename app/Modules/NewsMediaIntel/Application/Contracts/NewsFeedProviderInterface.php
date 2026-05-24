<?php

namespace App\Modules\NewsMediaIntel\Application\Contracts;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

interface NewsFeedProviderInterface
{
    public function key(): string;

    /**
     * @return array<int, NewsMentionDTO>
     */
    public function fetch(string $query): array;
}
