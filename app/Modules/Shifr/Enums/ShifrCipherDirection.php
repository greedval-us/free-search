<?php

namespace App\Modules\Shifr\Enums;

enum ShifrCipherDirection: string
{
    case Encrypt = 'encrypt';
    case Decrypt = 'decrypt';
    case Transform = 'transform';

    public static function ruleList(): string
    {
        return implode(',', array_map(static fn (self $case): string => $case->value, self::cases()));
    }
}

