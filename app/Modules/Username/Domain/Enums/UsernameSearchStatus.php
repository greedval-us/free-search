<?php

namespace App\Modules\Username\Domain\Enums;

enum UsernameSearchStatus: string
{
    case Found = 'found';
    case NotFound = 'not_found';
    case Unknown = 'unknown';
}
