<?php

namespace App\Modules\Shifr\DTO\Toolkit;

final class HashLookupDTO
{
    public function __construct(
        public readonly string $input,
        public readonly string $algorithm,
        public readonly ?string $hmacKey,
    ) {
    }
}
