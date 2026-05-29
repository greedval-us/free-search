<?php

namespace App\Modules\Telegram\Support;

enum TelegramReactionLabel: string
{
    case Paid = 'Paid';
    case Custom = 'Custom';
    case Reaction = 'Reaction';
}

