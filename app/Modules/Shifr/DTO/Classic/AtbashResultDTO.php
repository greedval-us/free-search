<?php

namespace App\Modules\Shifr\DTO\Classic;

use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;

class AtbashResultDTO implements ShifrResultDataInterface
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
