<?php

namespace App\Modules\Dorks\Domain\Contracts;

use App\Modules\Dorks\Domain\DTO\DorkResultItemDTO;

interface DorkSourceProviderInterface
{
    /**
     * @return array<int, DorkResultItemDTO>
     */
    public function search(string $dorkQuery, string $goal, int $limit): array;
}

