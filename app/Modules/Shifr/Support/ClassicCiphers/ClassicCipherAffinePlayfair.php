<?php

namespace App\Modules\Shifr\Support\ClassicCiphers;

final class ClassicCipherAffinePlayfair
{
    public function affineEncrypt(string $text, int $a, int $b): string
    {
        return $this->affine($text, $a, $b, false);
    }

    public function affineDecrypt(string $text, int $a, int $b): string
    {
        return $this->affine($text, $a, $b, true);
    }

    public function playfairEncrypt(string $text, string $key): string
    {
        return $this->playfair($text, $key, false);
    }

    public function playfairDecrypt(string $text, string $key): string
    {
        return $this->playfair($text, $key, true);
    }

    private function affine(string $text, int $a, int $b, bool $decrypt): string
    {
        $mod = 26;
        if ($this->gcd($a, $mod) !== 1) {
            return $text;
        }

        $inverse = $this->modInverse($a, $mod);
        if ($inverse === null) {
            return $text;
        }

        $output = '';
        foreach (mb_str_split($text) as $char) {
            if (!preg_match('/[A-Za-z]/', $char)) {
                $output .= $char;
                continue;
            }

            $isLower = ctype_lower($char);
            $base = ord($isLower ? 'a' : 'A');
            $x = ord($char) - $base;
            $normalizedB = (($b % $mod) + $mod) % $mod;

            $y = $decrypt
                ? ($inverse * (($x - $normalizedB + $mod) % $mod)) % $mod
                : (($a * $x + $normalizedB) % $mod);

            $output .= chr($base + $y);
        }

        return $output;
    }

    private function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);

        while ($b !== 0) {
            $tmp = $b;
            $b = $a % $b;
            $a = $tmp;
        }

        return $a;
    }

    private function modInverse(int $a, int $m): ?int
    {
        $a = (($a % $m) + $m) % $m;
        for ($x = 1; $x < $m; $x++) {
            if ((($a * $x) % $m) === 1) {
                return $x;
            }
        }

        return null;
    }

    private function playfair(string $text, string $key, bool $decrypt): string
    {
        [$matrix, $position] = $this->playfairMatrix($key);
        $pairs = $this->playfairPairs($text, $decrypt);
        $result = '';

        foreach ($pairs as [$a, $b]) {
            [$ar, $ac] = $position[$a];
            [$br, $bc] = $position[$b];

            if ($ar === $br) {
                $shift = $decrypt ? -1 : 1;
                $result .= $matrix[$ar][($ac + $shift + 5) % 5];
                $result .= $matrix[$br][($bc + $shift + 5) % 5];
                continue;
            }

            if ($ac === $bc) {
                $shift = $decrypt ? -1 : 1;
                $result .= $matrix[($ar + $shift + 5) % 5][$ac];
                $result .= $matrix[($br + $shift + 5) % 5][$bc];
                continue;
            }

            $result .= $matrix[$ar][$bc];
            $result .= $matrix[$br][$ac];
        }

        return $result;
    }

    /**
     * @return array{0: array<int, array<int, string>>, 1: array<string, array{0: int, 1: int}>}
     */
    private function playfairMatrix(string $key): array
    {
        $alphabet = 'ABCDEFGHIKLMNOPQRSTUVWXYZ';
        $normalizedKey = strtoupper(preg_replace('/[^A-Za-z]/', '', $key) ?? '');
        $normalizedKey = str_replace('J', 'I', $normalizedKey);
        $raw = $normalizedKey . $alphabet;

        $seen = [];
        $letters = [];
        foreach (str_split($raw) as $char) {
            if (isset($seen[$char])) {
                continue;
            }

            $seen[$char] = true;
            $letters[] = $char;
        }

        $matrix = [];
        $position = [];
        for ($i = 0; $i < 25; $i++) {
            $row = intdiv($i, 5);
            $col = $i % 5;
            $char = $letters[$i];
            $matrix[$row][$col] = $char;
            $position[$char] = [$row, $col];
        }

        return [$matrix, $position];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function playfairPairs(string $text, bool $decrypt): array
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z]/', '', $text) ?? '');
        $clean = str_replace('J', 'I', $clean);

        if ($clean === '') {
            return [];
        }

        $chars = str_split($clean);
        $pairs = [];

        if ($decrypt) {
            for ($i = 0, $len = count($chars); $i < $len; $i += 2) {
                $pairs[] = [$chars[$i], $chars[$i + 1] ?? 'X'];
            }

            return $pairs;
        }

        for ($i = 0, $len = count($chars); $i < $len;) {
            $a = $chars[$i];
            $b = $chars[$i + 1] ?? 'X';

            if ($a === $b) {
                $pairs[] = [$a, 'X'];
                $i++;
                continue;
            }

            $pairs[] = [$a, $b];
            $i += 2;
        }

        return $pairs;
    }
}
