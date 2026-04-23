<?php

namespace App\Modules\Dorks\Application\DTO;

final class DorkSearchQueryDTO
{
    public function __construct(
        public readonly string $target,
        public readonly string $goal,
    ) {
    }
}

