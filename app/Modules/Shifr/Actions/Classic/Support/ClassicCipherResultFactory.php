<?php

namespace App\Modules\Shifr\Actions\Classic\Support;

use App\Modules\Shifr\DTO\Classic\AtbashResultDTO;

final class ClassicCipherResultFactory
{
    /**
     * @return array<string, mixed>
     */
    public function fromOriginalAndResult(string $original, string $result): array
    {
        return (new AtbashResultDTO(
            original: $original,
            result: $result,
        ))->toArray();
    }
}
