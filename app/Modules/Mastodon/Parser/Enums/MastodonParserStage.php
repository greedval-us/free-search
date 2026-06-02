<?php

namespace App\Modules\Mastodon\Parser\Enums;

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
