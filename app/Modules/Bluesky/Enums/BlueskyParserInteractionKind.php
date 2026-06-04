<?php

namespace App\Modules\Bluesky\Enums;

enum BlueskyParserInteractionKind: string
{
    case Likes = 'likes';
    case Reposts = 'reposts';
    case Replies = 'replies';
}
