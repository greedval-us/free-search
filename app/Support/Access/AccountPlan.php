<?php

namespace App\Support\Access;

enum AccountPlan: string
{
    case Free = 'free';
    case Plus = 'plus';
    case Pro = 'pro';

    public static function fromNullable(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Free;
    }
}
