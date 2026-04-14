<?php

namespace App\Modules\Username\DTO;

final class UsernameSearchQueryDTO
{
    public function __construct(
        public readonly string $username,
    ) {
    }
}
