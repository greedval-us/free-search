<?php

namespace App\Modules\ParserSupport\Enums;

enum ParserRunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Stopped = 'stopped';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Stopped => true,
            self::Running => false,
        };
    }

    public function isDownloadable(): bool
    {
        return match ($this) {
            self::Completed, self::Stopped, self::Failed => true,
            self::Running => false,
        };
    }
}
