<?php

namespace App\Modules\Shifr\DTO\Toolkit\Results;

use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;

final class HashResultDTO implements ShifrResultDataInterface
{
    /**
     * @param array<string, mixed> $analysis
     */
    public function __construct(
        public readonly string $algorithm,
        public readonly string $mode,
        public readonly string $value,
        public readonly int $inputLength,
        public readonly array $analysis,
    ) {
    }

    public function toArray(): array
    {
        return [
            'algorithm' => $this->algorithm,
            'mode' => $this->mode,
            'value' => $this->value,
            'inputLength' => $this->inputLength,
            'analysis' => $this->analysis,
        ];
    }
}

