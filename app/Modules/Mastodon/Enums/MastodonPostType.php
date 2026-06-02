<?php

namespace App\Modules\Mastodon\Enums;

enum MastodonPostType: string
{
    case Original = 'original';
    case Reply = 'reply';
    case Boost = 'boost';
}
