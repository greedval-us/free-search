<?php

namespace App\Modules\YouTube\Enums;

enum YouTubeParserStage: string
{
    case Idle = 'idle';
    case Comments = 'comments';
    case Replies = 'replies';
    case Finishing = 'finishing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Stopped = 'stopped';
}
