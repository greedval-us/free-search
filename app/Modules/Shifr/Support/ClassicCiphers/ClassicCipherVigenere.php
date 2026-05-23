<?php

namespace App\Modules\Shifr\Support\ClassicCiphers;

use App\Modules\Shifr\Support\CipherAlphabets;

final class ClassicCipherVigenere
{
    public function encrypt(string $text, string $key): string
    {
        return $this->transform($text, $key, false);
    }

    public function decrypt(string $text, string $key): string
    {
        return $this->transform($text, $key, true);
    }

    private function transform(string $text, string $key, bool $decrypt): string
    {
        $latin = CipherAlphabets::LATIN_UPPER;
        $cyr = CipherAlphabets::CYRILLIC_UPPER;
        $normalizedKey = mb_strtoupper($key);
        $keyChars = mb_str_split($normalizedKey);
        $keyLen = count($keyChars);

        if ($keyLen === 0) {
            return $text;
        }

        $output = '';
        $keyIndex = 0;

        foreach (mb_str_split($text) as $char) {
            $upper = mb_strtoupper($char);
            [$alphabet, $charPos] = $this->detectAlphabetAndPosition($upper, $latin, $cyr);
            if ($alphabet === null || $charPos === null) {
                $output .= $char;
                continue;
            }

            $keyChar = $keyChars[$keyIndex % $keyLen];
            $shift = mb_strpos($alphabet, $keyChar);
            if ($shift === false) {
                $output .= $char;
                continue;
            }

            $mod = mb_strlen($alphabet);
            $newPos = $decrypt
                ? ($charPos - $shift + $mod) % $mod
                : ($charPos + $shift) % $mod;

            $newChar = mb_substr($alphabet, $newPos, 1);
            $output .= $this->isLowerMb($char) ? mb_strtolower($newChar) : $newChar;
            $keyIndex++;
        }

        return $output;
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function detectAlphabetAndPosition(string $char, string $latin, string $cyr): array
    {
        $latinPos = mb_strpos($latin, $char);
        if ($latinPos !== false) {
            return [$latin, $latinPos];
        }

        $cyrPos = mb_strpos($cyr, $char);
        if ($cyrPos !== false) {
            return [$cyr, $cyrPos];
        }

        return [null, null];
    }

    private function isLowerMb(string $char): bool
    {
        return mb_strtolower($char) === $char && mb_strtoupper($char) !== $char;
    }
}
