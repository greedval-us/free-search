<?php

namespace App\Modules\Bluesky\Parser\Enums;

enum BlueskyParserStage: string
{
    case Idle = 'idle';
    case Profile = 'profile';
    case Feed = 'feed';
    case Followers = 'followers';
    case Follows = 'follows';
    case Interactions = 'interactions';
    case Finishing = 'finishing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Stopped = 'stopped';
}
