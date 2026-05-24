<?php

namespace App\Modules\Shifr\Support;

final class CipherAlphabets
{
    public const LATIN_LOWER = 'abcdefghijklmnopqrstuvwxyz';
    public const LATIN_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const CYRILLIC_LOWER = "\u{0430}\u{0431}\u{0432}\u{0433}\u{0434}\u{0435}\u{0451}\u{0436}\u{0437}\u{0438}\u{0439}\u{043A}\u{043B}\u{043C}\u{043D}\u{043E}\u{043F}\u{0440}\u{0441}\u{0442}\u{0443}\u{0444}\u{0445}\u{0446}\u{0447}\u{0448}\u{0449}\u{044A}\u{044B}\u{044C}\u{044D}\u{044E}\u{044F}";
    public const CYRILLIC_UPPER = "\u{0410}\u{0411}\u{0412}\u{0413}\u{0414}\u{0415}\u{0401}\u{0416}\u{0417}\u{0418}\u{0419}\u{041A}\u{041B}\u{041C}\u{041D}\u{041E}\u{041F}\u{0420}\u{0421}\u{0422}\u{0423}\u{0424}\u{0425}\u{0426}\u{0427}\u{0428}\u{0429}\u{042A}\u{042B}\u{042C}\u{042D}\u{042E}\u{042F}";

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
