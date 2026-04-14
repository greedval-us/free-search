<?php

namespace App\Modules\Username\Contracts;

use App\Modules\Username\DTO\UsernameSourceCheckResultDTO;
use App\Modules\Username\DTO\UsernameSourceDTO;

interface UsernameSourceCheckerInterface
{
    /**
     * @param array<int, UsernameSourceDTO> $sources
     * @return array<int, UsernameSourceCheckResultDTO>
     */
    public function checkMany(array $sources, string $username): array;
}
