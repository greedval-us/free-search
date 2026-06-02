<?php

namespace App\Modules\Telegram\Enums;

enum TelegramReactionLabel: string
{
    case Paid = 'Paid';
    case Custom = 'Custom';
    case Reaction = 'Reaction';
}
