<?php

namespace App\Modules\Shifr\Actions;

use App\Modules\Shifr\Support\CipherAlphabets;

abstract class AbstractShiftCipherAction
{
    protected function process(string $text, int $shift): string
    {
        $output = '';

        foreach (mb_str_split($text) as $char) {
            $output .= $this->shiftChar($char, $shift);
        }

        return $output;
    }

    protected function shiftChar(string $char, int $shift): string
    {
        foreach (CipherAlphabets::all() as $alphabet) {
            if (mb_strpos($alphabet, $char) !== false) {
                return $this->shiftInsideAlphabet($char, $alphabet, $shift);
            }
        }

        return $char;
    }

    protected function shiftInsideAlphabet(string $char, string $alphabet, int $shift): string
    {
        $len = mb_strlen($alphabet);
        $index = mb_strpos($alphabet, $char);

        $newIndex = ($index + $shift) % $len;
        if ($newIndex < 0) {
            $newIndex += $len;
        }

        return mb_substr($alphabet, $newIndex, 1);
    }
}

