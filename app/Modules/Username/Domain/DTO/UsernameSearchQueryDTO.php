<?php

namespace App\Modules\Username\Domain\DTO;

final class UsernameSearchQueryDTO
{
    public function __construct(
        public readonly string $username,
    ) {
    }
}
