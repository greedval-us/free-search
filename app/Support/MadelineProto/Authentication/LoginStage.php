<?php

declare(strict_types=1);

namespace App\Support\MadelineProto\Authentication;

enum LoginStage: string
{
    case Phone = 'phone';
    case Code = 'code';
    case Password = 'password';
    case Ready = 'ready';
    case Unsupported = 'unsupported';
}
