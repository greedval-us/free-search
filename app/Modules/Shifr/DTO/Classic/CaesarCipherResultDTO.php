<?php

namespace App\Modules\Shifr\DTO\Classic;

use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;

class CaesarCipherResultDTO implements ShifrResultDataInterface
{
    public function __construct(
        public readonly string $original,
        public readonly int    $shift,
        public readonly string $result
    ) {}

    public function toArray(): array
    {
        return [
            'original' => $this->original,
            'shift'    => $this->shift,
            'result'   => $this->result,
        ];
    }
}
