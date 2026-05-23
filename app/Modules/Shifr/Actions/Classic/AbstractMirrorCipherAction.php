<?php

namespace App\Modules\Shifr\Actions\Classic;

use App\Modules\Shifr\Support\CipherAlphabets;

abstract class AbstractMirrorCipherAction
{
    protected function process(string $text): string
    {
        $output = '';

        foreach (mb_str_split($text) as $char) {
            $output .= $this->invertChar($char);
        }

        return $output;
    }

    protected function invertChar(string $char): string
    {
        foreach (CipherAlphabets::all() as $alphabet) {
            $pos = mb_strpos($alphabet, $char);
            if ($pos !== false) {
                $len = mb_strlen($alphabet);
                $inverseIndex = $len - $pos - 1;

                return mb_substr($alphabet, $inverseIndex, 1);
            }
        }

        return $char;
    }
}
