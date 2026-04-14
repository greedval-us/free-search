<?php

namespace App\Modules\Username\Domain\Contracts;

use App\Modules\Username\Domain\DTO\UsernameSourceCheckResultDTO;
use App\Modules\Username\Domain\DTO\UsernameSourceDTO;

interface UsernameSourceCheckerInterface
{
    /**
     * @param array<int, UsernameSourceDTO> $sources
     * @return array<int, UsernameSourceCheckResultDTO>
     */
    public function checkMany(array $sources, string $username): array;
}
