<?php

namespace App\Modules\Shifr\Actions;

use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;
use App\Modules\Shifr\Support\CipherAlphabets;

final class Rot13TransformAction
{
    public function execute(AtbashRequestDTO $dto): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $dto->message,
            result: $this->transform($dto->message),
        );
    }

    private function transform(string $text): string
    {
        $output = '';

        foreach (mb_str_split($text) as $char) {
            $output .= $this->rotateChar($char);
        }

        return $output;
    }

    private function rotateChar(string $char): string
    {
        foreach (CipherAlphabets::all() as $alphabet) {
            $index = mb_strpos($alphabet, $char);
            if ($index === false) {
                continue;
            }

            // ROT13 semantics for latin, and practical +13 shift for cyrillic.
            $shift = 13;
            $len = mb_strlen($alphabet);
            $newIndex = ($index + $shift) % $len;

            return mb_substr($alphabet, $newIndex, 1);
        }

        return $char;
    }
}
