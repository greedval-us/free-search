<?php

namespace App\Modules\Shifr\DTO;

class AtbashResultDTO
{
    public function __construct(
        public readonly string $original,
        public readonly string $result
    ) {}

    public function toArray(): array
    {
        return [
            'original' => $this->original,
            'result'   => $this->result,
        ];
    }
}
