<?php

namespace App\Modules\Telegram\Enums;

enum TelegramParserStage: string
{
    case Idle = 'idle';
    case Messages = 'messages';
    case Comments = 'comments';
    case Finishing = 'finishing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Stopped = 'stopped';
}
