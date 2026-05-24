<?php

namespace App\Modules\Shifr\DTO\Toolkit\Results;

use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;

final class TransformResultDTO implements ShifrResultDataInterface
{
    public function __construct(
        public readonly string $operation,
        public readonly int $inputLength,
        public readonly int $outputLength,
        public readonly string $value,
    ) {
    }

    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'inputLength' => $this->inputLength,
            'outputLength' => $this->outputLength,
            'value' => $this->value,
        ];
    }
}

