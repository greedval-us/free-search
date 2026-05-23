<?php

namespace App\Modules\Shifr\Support\ClassicCiphers;

class ClassicCipherTransposition
{
    public function columnarEncrypt(string $text, string $key): string
    {
        $normalizedKey = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $key) ?? '');
        if ($normalizedKey === '') {
            return $text;
        }

        $cols = strlen($normalizedKey);
        $rows = (int) ceil(strlen($text) / $cols);
        $grid = array_fill(0, $rows, array_fill(0, $cols, null));

        $idx = 0;
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                if ($idx < strlen($text)) {
                    $grid[$r][$c] = $text[$idx];
                    $idx++;
                }
            }
        }

        $order = $this->columnOrder($normalizedKey);
        $result = '';
        foreach ($order as $c) {
            for ($r = 0; $r < $rows; $r++) {
                if ($grid[$r][$c] !== null) {
                    $result .= $grid[$r][$c];
                }
            }
        }

        return $result;
    }

    public function columnarDecrypt(string $text, string $key): string
    {
        $normalizedKey = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $key) ?? '');
        if ($normalizedKey === '') {
            return $text;
        }

        $cols = strlen($normalizedKey);
        $length = strlen($text);
        if ($length === 0) {
            return $text;
        }

        $rows = (int) ceil($length / $cols);
        $remainder = $length % $cols;
        $order = $this->columnOrder($normalizedKey);

        $colLengths = array_fill(0, $cols, intdiv($length, $cols));
        if ($remainder > 0) {
            for ($c = 0; $c < $remainder; $c++) {
                $colLengths[$c]++;
            }
        }

        $grid = array_fill(0, $rows, array_fill(0, $cols, null));
        $idx = 0;
        foreach ($order as $c) {
            $size = $colLengths[$c];
            for ($r = 0; $r < $size; $r++) {
                $grid[$r][$c] = $text[$idx] ?? null;
                $idx++;
            }
        }

        $result = '';
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                if ($grid[$r][$c] !== null) {
                    $result .= $grid[$r][$c];
                }
            }
        }

        return $result;
    }

    public function morseEncode(string $text, string $separator): string
    {
        $separator = $separator !== '' ? $separator : '/';
        $map = $this->morseMap();
        $tokens = [];

        foreach (mb_str_split(mb_strtoupper($text)) as $char) {
            if ($char === ' ') {
                $tokens[] = $separator;
                continue;
            }

            $tokens[] = $map[$char] ?? $char;
        }

        return implode(' ', $tokens);
    }

    public function morseDecode(string $text, string $separator): string
    {
        $separator = $separator !== '' ? $separator : '/';
        $reverse = array_flip($this->morseMap());
        $parts = preg_split('/\s+/', trim($text)) ?: [];
        $result = '';

        foreach ($parts as $token) {
            if ($token === $separator) {
                $result .= ' ';
                continue;
            }

            $result .= $reverse[$token] ?? $token;
        }

        return $result;
    }

    /**
     * @return array<int, int>
     */
    private function columnOrder(string $key): array
    {
        $pairs = [];
        for ($i = 0, $len = strlen($key); $i < $len; $i++) {
            $pairs[] = ['char' => $key[$i], 'idx' => $i];
        }

        usort($pairs, static function (array $a, array $b): int {
            return $a['char'] === $b['char'] ? $a['idx'] <=> $b['idx'] : $a['char'] <=> $b['char'];
        });

        return array_map(static fn (array $pair): int => $pair['idx'], $pairs);
    }

    /**
     * @return array<string, string>
     */
    private function morseMap(): array
    {
        return [
            'A' => '.-', 'B' => '-...', 'C' => '-.-.', 'D' => '-..', 'E' => '.', 'F' => '..-.',
            'G' => '--.', 'H' => '....', 'I' => '..', 'J' => '.---', 'K' => '-.-', 'L' => '.-..',
            'M' => '--', 'N' => '-.', 'O' => '---', 'P' => '.--.', 'Q' => '--.-', 'R' => '.-.',
            'S' => '...', 'T' => '-', 'U' => '..-', 'V' => '...-', 'W' => '.--', 'X' => '-..-',
            'Y' => '-.--', 'Z' => '--..',
            'А' => '.-', 'Б' => '-...', 'В' => '.--', 'Г' => '--.', 'Д' => '-..', 'Е' => '.',
            'Ё' => '.', 'Ж' => '...-', 'З' => '--..', 'И' => '..', 'Й' => '.---', 'К' => '-.-',
            'Л' => '.-..', 'М' => '--', 'Н' => '-.', 'О' => '---', 'П' => '.--.', 'Р' => '.-.',
            'С' => '...', 'Т' => '-', 'У' => '..-', 'Ф' => '..-.', 'Х' => '....', 'Ц' => '-.-.',
            'Ч' => '---.', 'Ш' => '----', 'Щ' => '--.-', 'Ъ' => '--.--', 'Ы' => '-.--', 'Ь' => '-..-',
            'Э' => '..-..', 'Ю' => '..--', 'Я' => '.-.-',
            '0' => '-----', '1' => '.----', '2' => '..---', '3' => '...--', '4' => '....-',
            '5' => '.....', '6' => '-....', '7' => '--...', '8' => '---..', '9' => '----.',
            '.' => '.-.-.-', ',' => '--..--', '?' => '..--..', '!' => '-.-.--', ':' => '---...',
            ';' => '-.-.-.', '(' => '-.--.', ')' => '-.--.-', '-' => '-....-', '"' => '.-..-.',
            '\'' => '.----.', '/' => '-..-.', '@' => '.--.-.', '=' => '-...-',
        ];
    }
}
