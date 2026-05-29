<?php

namespace App\Modules\Telegram\Support;

enum TelegramReactionIdentityKind: string
{
    case Document = 'document';
    case Emoji = 'emoji';
    case Paid = 'paid';
    case Type = 'type';
    case Label = 'label';

    public function key(string|int $value): string
    {
        if ($this === self::Paid) {
            return self::Paid->value;
        }

        return $this->value . ':' . $value;
    }
}

