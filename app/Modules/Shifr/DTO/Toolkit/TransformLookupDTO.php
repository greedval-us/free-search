<?php

namespace App\Modules\Shifr\DTO\Toolkit;

final class TransformLookupDTO
{
    public function __construct(
        public readonly string $input,
        public readonly string $operation,
    ) {
    }
}
