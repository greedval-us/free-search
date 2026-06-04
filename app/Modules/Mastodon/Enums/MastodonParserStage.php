<?php

namespace App\Modules\Mastodon\Enums;

enum MastodonParserStage: string
{
    case Idle = 'idle';
    case Statuses = 'statuses';
    case Comments = 'comments';
    case Finishing = 'finishing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Stopped = 'stopped';
}
