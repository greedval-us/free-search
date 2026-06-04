<?php

namespace App\Modules\ParserSupport\Enums;

enum ParserRunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Stopped = 'stopped';
}
