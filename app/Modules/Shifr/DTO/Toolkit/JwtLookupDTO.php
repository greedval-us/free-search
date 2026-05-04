<?php

namespace App\Modules\Shifr\DTO\Toolkit;

final class JwtLookupDTO
{
    public function __construct(
        public readonly string $token,
        public readonly ?string $secret,
    ) {
    }
}
