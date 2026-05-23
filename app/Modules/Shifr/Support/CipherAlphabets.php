<?php

namespace App\Modules\Shifr\Support;

final class CipherAlphabets
{
    public const LATIN_LOWER = 'abcdefghijklmnopqrstuvwxyz';
    public const LATIN_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const CYRILLIC_LOWER = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';
    public const CYRILLIC_UPPER = 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::LATIN_LOWER,
            self::LATIN_UPPER,
            self::CYRILLIC_LOWER,
            self::CYRILLIC_UPPER,
        ];
    }
}
