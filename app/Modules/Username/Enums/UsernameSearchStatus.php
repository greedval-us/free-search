<?php

namespace App\Modules\Username\Enums;

enum UsernameSearchStatus: string
{
    case Found = 'found';
    case NotFound = 'not_found';
    case Unknown = 'unknown';
}
