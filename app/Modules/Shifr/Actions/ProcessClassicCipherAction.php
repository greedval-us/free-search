<?php

namespace App\Modules\Shifr\Actions;

use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;
use App\Modules\Shifr\DTO\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\ClassicCipherLookupDTO;
use App\Modules\Shifr\Support\ClassicCipherTransposition;

final class ProcessClassicCipherAction
{
    public function __construct(
        private readonly CaesarEncryptAction $encryptCaesar,
        private readonly CaesarDecryptAction $decryptCaesar,
        private readonly AtbashEncryptAction $encryptAtbash,
        private readonly AtbashDecryptAction $decryptAtbash,
        private readonly Rot13TransformAction $rot13Transform,
        private readonly Rot47TransformAction $rot47Transform,
        private readonly ClassicCipherTransposition $transposition,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function execute(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->cipher) {
            'caesar' => $this->resolveCaesar($dto),
            'atbash' => $this->resolveAtbash($dto),
            'rot13', 'rot47', 'rot5' => $this->resolveRot($dto),
            'vigenere' => $this->resolveVigenere($dto),
            'rail_fence' => $this->resolveRailFence($dto),
            'xor' => $this->resolveXor($dto),
            'affine' => $this->resolveAffine($dto),
            'playfair' => $this->resolvePlayfair($dto),
            'columnar' => $this->resolveColumnar($dto),
            'morse' => $this->resolveMorse($dto),
            default => null,
        };
    }

    private function resolveCaesar(ClassicCipherLookupDTO $dto): ?array
    {
        $request = new CaesarCipherRequestDTO([
            'message' => $dto->text,
            'shift' => $dto->shift,
        ]);

        return match ($dto->direction) {
            'encrypt' => $this->encryptCaesar->execute($request)->toArray(),
            'decrypt' => $this->decryptCaesar->execute($request)->toArray(),
            default => null,
        };
    }

    private function resolveAtbash(ClassicCipherLookupDTO $dto): ?array
    {
        $request = new AtbashRequestDTO([
            'message' => $dto->text,
        ]);

        return match ($dto->direction) {
            'encrypt' => $this->encryptAtbash->execute($request)->toArray(),
            'decrypt' => $this->decryptAtbash->execute($request)->toArray(),
            default => null,
        };
    }

    private function resolveRot(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->direction !== 'transform') {
            return null;
        }

        $request = new AtbashRequestDTO([
            'message' => $dto->text,
        ]);

        return match ($dto->cipher) {
            'rot13' => $this->rot13Transform->execute($request)->toArray(),
            'rot47' => $this->rot47Transform->execute($request)->toArray(),
            'rot5' => $this->transformRot5($dto->text)->toArray(),
            default => null,
        };
    }

    private function resolveVigenere(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->key === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->encryptVigenere($dto->text, $dto->key)->toArray(),
            'decrypt' => $this->decryptVigenere($dto->text, $dto->key)->toArray(),
            default => null,
        };
    }

    private function resolveRailFence(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->direction) {
            'encrypt' => $this->encryptRailFence($dto->text, $dto->rails)->toArray(),
            'decrypt' => $this->decryptRailFence($dto->text, $dto->rails)->toArray(),
            default => null,
        };
    }

    private function resolveXor(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->xorKey === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->encryptXor($dto->text, $dto->xorKey)->toArray(),
            'decrypt' => $this->decryptXor($dto->text, $dto->xorKey)->toArray(),
            default => null,
        };
    }

    private function resolveAffine(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->direction) {
            'encrypt' => $this->encryptAffine($dto->text, $dto->affineA, $dto->affineB)->toArray(),
            'decrypt' => $this->decryptAffine($dto->text, $dto->affineA, $dto->affineB)->toArray(),
            default => null,
        };
    }

    private function resolvePlayfair(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->playfairKey === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->encryptPlayfair($dto->text, $dto->playfairKey)->toArray(),
            'decrypt' => $this->decryptPlayfair($dto->text, $dto->playfairKey)->toArray(),
            default => null,
        };
    }

    private function resolveColumnar(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->columnKey === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->encryptColumnar($dto->text, $dto->columnKey)->toArray(),
            'decrypt' => $this->decryptColumnar($dto->text, $dto->columnKey)->toArray(),
            default => null,
        };
    }

    private function resolveMorse(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->direction) {
            'encrypt' => $this->encryptMorse($dto->text, $dto->morseSeparator)->toArray(),
            'decrypt' => $this->decryptMorse($dto->text, $dto->morseSeparator)->toArray(),
            default => null,
        };
    }

    private function transformRot5(string $message): AtbashResultDTO
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

    private function encryptVigenere(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->vigenere($message, $key, false));
    }

    private function decryptVigenere(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->vigenere($message, $key, true));
    }

    private function encryptRailFence(string $message, int $rails): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->railFenceEncrypt($message, $rails));
    }

    private function decryptRailFence(string $message, int $rails): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->railFenceDecrypt($message, $rails));
    }

    private function encryptXor(string $message, string $key): AtbashResultDTO
    {
        if ($key === '' || $message === '') {
            return new AtbashResultDTO(original: $message, result: $message);
        }

        return new AtbashResultDTO(original: $message, result: base64_encode($this->xorBytes($message, $key)));
    }

    private function decryptXor(string $message, string $key): AtbashResultDTO
    {
        if ($key === '' || $message === '') {
            return new AtbashResultDTO(original: $message, result: $message);
        }

        $decoded = base64_decode($message, true);
        if ($decoded === false) {
            return new AtbashResultDTO(original: $message, result: $message);
        }

        return new AtbashResultDTO(original: $message, result: $this->xorBytes($decoded, $key));
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

    private function encryptAffine(string $message, int $a, int $b): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->affine($message, $a, $b, false));
    }

    private function decryptAffine(string $message, int $a, int $b): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->affine($message, $a, $b, true));
    }

    private function encryptPlayfair(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->playfair($message, $key, false));
    }

    private function decryptPlayfair(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->playfair($message, $key, true));
    }

    private function encryptColumnar(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->transposition->columnarEncrypt($message, $key));
    }

    private function decryptColumnar(string $message, string $key): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->transposition->columnarDecrypt($message, $key));
    }

    private function encryptMorse(string $message, string $separator): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->transposition->morseEncode($message, $separator));
    }

    private function decryptMorse(string $message, string $separator): AtbashResultDTO
    {
        return new AtbashResultDTO(original: $message, result: $this->transposition->morseDecode($message, $separator));
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
