<?php

namespace App\Modules\Shifr;

use App\Modules\Shifr\Actions\AtbashDecryptAction;
use App\Modules\Shifr\Actions\AtbashEncryptAction;
use App\Modules\Shifr\Actions\CaesarDecryptAction;
use App\Modules\Shifr\Actions\CaesarEncryptAction;
use App\Modules\Shifr\Actions\Rot13TransformAction;
use App\Modules\Shifr\Actions\Rot47TransformAction;
use App\Modules\Shifr\Contracts\ShifrServiceInterface;
use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;
use App\Modules\Shifr\DTO\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\CaesarCipherResultDTO;

class ShifrService implements ShifrServiceInterface
{
    public function __construct(
        private readonly CaesarEncryptAction $encryptCaesar,
        private readonly CaesarDecryptAction $decryptCaesar,
        private readonly AtbashEncryptAction $encryptAtbash,
        private readonly AtbashDecryptAction $decryptAtbash,
        private readonly Rot13TransformAction $rot13Transform,
        private readonly Rot47TransformAction $rot47Transform,
    ) {}

    public function encryptCaesar(string $message, int $shift): CaesarCipherResultDTO
    {
        $dto = new CaesarCipherRequestDTO([
            'message' => $message,
            'shift' => $shift,
        ]);

        return $this->encryptCaesar->execute($dto);
    }

    public function decryptCaesar(string $message, int $shift): CaesarCipherResultDTO
    {
        $dto = new CaesarCipherRequestDTO([
            'message' => $message,
            'shift' => $shift,
        ]);

        return $this->decryptCaesar->execute($dto);
    }

    public function encryptAtbash(string $message): AtbashResultDTO
    {
        $dto = new AtbashRequestDTO([
            'message' => $message,
        ]);

        return $this->encryptAtbash->execute($dto);
    }

    public function decryptAtbash(string $message): AtbashResultDTO
    {
        $dto = new AtbashRequestDTO([
            'message' => $message,
        ]);

        return $this->decryptAtbash->execute($dto);
    }

    public function transformRot13(string $message): AtbashResultDTO
    {
        $dto = new AtbashRequestDTO([
            'message' => $message,
        ]);

        return $this->rot13Transform->execute($dto);
    }

    public function transformRot47(string $message): AtbashResultDTO
    {
        $dto = new AtbashRequestDTO([
            'message' => $message,
        ]);

        return $this->rot47Transform->execute($dto);
    }

    public function transformRot5(string $message): AtbashResultDTO
    {
        $result = preg_replace_callback('/\d/', static function (array $matches): string {
            $digit = (int) $matches[0];

            return (string) (($digit + 5) % 10);
        }, $message);

        return new AtbashResultDTO(
            original: $message,
            result: $result ?? $message,
        );
    }

    public function encryptVigenere(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->vigenere($message, $key, false),
        );
    }

    public function decryptVigenere(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->vigenere($message, $key, true),
        );
    }

    public function encryptRailFence(string $message, int $rails): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->railFenceEncrypt($message, $rails),
        );
    }

    public function decryptRailFence(string $message, int $rails): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->railFenceDecrypt($message, $rails),
        );
    }

    public function encryptXor(string $message, string $key): AtbashResultDTO
    {
        if ($key === '' || $message === '') {
            return new AtbashResultDTO(
                original: $message,
                result: $message,
            );
        }

        $result = $this->xorBytes($message, $key);

        return new AtbashResultDTO(
            original: $message,
            result: base64_encode($result),
        );
    }

    public function decryptXor(string $message, string $key): AtbashResultDTO
    {
        if ($key === '' || $message === '') {
            return new AtbashResultDTO(
                original: $message,
                result: $message,
            );
        }

        $decoded = base64_decode($message, true);
        if ($decoded === false) {
            return new AtbashResultDTO(
                original: $message,
                result: $message,
            );
        }

        return new AtbashResultDTO(
            original: $message,
            result: $this->xorBytes($decoded, $key),
        );
    }

    private function xorBytes(string $input, string $key): string
    {
        $keyBytes = array_values(unpack('C*', $key) ?: []);
        $inputBytes = array_values(unpack('C*', $input) ?: []);
        $keyLength = count($keyBytes);

        if ($keyLength === 0) {
            return $input;
        }

        foreach ($inputBytes as $index => $byte) {
            $inputBytes[$index] = $byte ^ $keyBytes[$index % $keyLength];
        }

        return pack('C*', ...$inputBytes) ?: '';
    }

    public function encryptAffine(string $message, int $a, int $b): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->affine($message, $a, $b, false),
        );
    }

    public function decryptAffine(string $message, int $a, int $b): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->affine($message, $a, $b, true),
        );
    }

    public function encryptPlayfair(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->playfair($message, $key, false),
        );
    }

    public function decryptPlayfair(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->playfair($message, $key, true),
        );
    }

    public function encryptColumnar(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->columnarEncrypt($message, $key),
        );
    }

    public function decryptColumnar(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->columnarDecrypt($message, $key),
        );
    }

    public function encryptMorse(string $message, string $separator): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->morseEncode($message, $separator),
        );
    }

    public function decryptMorse(string $message, string $separator): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $message,
            result: $this->morseDecode($message, $separator),
        );
    }

    private function vigenere(string $text, string $key, bool $decrypt): string
    {
        $latin = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $cyr = 'АБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';
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

    private function railFenceEncrypt(string $text, int $rails): string
    {
        $rails = max(2, $rails);
        $chars = mb_str_split($text);
        if (count($chars) <= 1) {
            return $text;
        }

        $rows = array_fill(0, $rails, '');
        $row = 0;
        $direction = 1;

        foreach ($chars as $char) {
            $rows[$row] .= $char;
            $row += $direction;
            if ($row === 0 || $row === $rails - 1) {
                $direction *= -1;
            }
        }

        return implode('', $rows);
    }

    private function railFenceDecrypt(string $text, int $rails): string
    {
        $rails = max(2, $rails);
        $chars = mb_str_split($text);
        $length = count($chars);
        if ($length <= 1) {
            return $text;
        }

        $pattern = [];
        $row = 0;
        $direction = 1;
        for ($i = 0; $i < $length; $i++) {
            $pattern[] = $row;
            $row += $direction;
            if ($row === 0 || $row === $rails - 1) {
                $direction *= -1;
            }
        }

        $counts = array_fill(0, $rails, 0);
        foreach ($pattern as $r) {
            $counts[$r]++;
        }

        $rows = [];
        $offset = 0;
        for ($r = 0; $r < $rails; $r++) {
            $rows[$r] = array_slice($chars, $offset, $counts[$r]);
            $offset += $counts[$r];
        }

        $indices = array_fill(0, $rails, 0);
        $output = '';
        foreach ($pattern as $r) {
            $output .= $rows[$r][$indices[$r]] ?? '';
            $indices[$r]++;
        }

        return $output;
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
            for ($i = 0; $i < count($chars); $i += 2) {
                $a = $chars[$i];
                $b = $chars[$i + 1] ?? 'X';
                $pairs[] = [$a, $b];
            }

            return $pairs;
        }

        for ($i = 0; $i < count($chars);) {
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

        if (count($pairs) > 0 && strlen(implode('', end($pairs))) < 2) {
            $last = array_pop($pairs);
            $pairs[] = [$last[0], 'X'];
        }

        return $pairs;
    }

    private function columnarEncrypt(string $text, string $key): string
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

    private function columnarDecrypt(string $text, string $key): string
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

    private function columnOrder(string $key): array
    {
        $pairs = [];
        for ($i = 0; $i < strlen($key); $i++) {
            $pairs[] = ['char' => $key[$i], 'idx' => $i];
        }

        usort($pairs, static function (array $a, array $b): int {
            if ($a['char'] === $b['char']) {
                return $a['idx'] <=> $b['idx'];
            }

            return $a['char'] <=> $b['char'];
        });

        return array_map(static fn (array $pair): int => $pair['idx'], $pairs);
    }

    private function morseEncode(string $text, string $separator): string
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

    private function morseDecode(string $text, string $separator): string
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
