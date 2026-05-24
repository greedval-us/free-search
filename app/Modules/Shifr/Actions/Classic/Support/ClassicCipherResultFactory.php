<?php

namespace App\Modules\Shifr\Actions\Classic\Support;

use App\Modules\Shifr\DTO\Classic\AtbashResultDTO;

final class ClassicCipherResultFactory
{
    public function fromOriginalAndResult(string $original, string $result): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $original,
            result: $result,
        );
    }
}
