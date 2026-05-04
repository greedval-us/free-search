<?php

namespace App\Modules\Shifr\DTO\Toolkit;

final class IocLookupDTO
{
    public function __construct(
        public readonly string $input,
    ) {
    }
}
