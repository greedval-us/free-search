<?php

namespace App\Modules\Shifr\DTO;

final class ClassicCipherLookupDTO
{
    public function __construct(
        public readonly string $text,
        public readonly string $cipher,
        public readonly string $direction,
        public readonly int $shift,
        public readonly string $key,
        public readonly int $rails,
        public readonly string $xorKey,
        public readonly int $affineA,
        public readonly int $affineB,
        public readonly string $playfairKey,
        public readonly string $columnKey,
        public readonly string $morseSeparator,
    ) {
    }
}

