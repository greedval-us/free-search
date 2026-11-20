<?php

namespace App\Modules\ParserSupport;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

final class ParserRunLocks
{
    private const START_LOCK_SECONDS = 10;

    public static function start(string $module, int $userId): Lock
    {
        return Cache::lock("parser-run:start:{$module}:{$userId}", self::START_LOCK_SECONDS);
    }

    public static function advance(string $module, int $userId, string $runId): Lock
    {
        return Cache::lock("parser-run:advance:{$module}:{$userId}:{$runId}", ParserRunConfig::EXECUTION_LOCK_SECONDS);
    }
}
