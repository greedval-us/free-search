<?php

namespace App\Modules\ParserSupport;

use App\Jobs\ProcessParserRun;
use LogicException;

final class ParserRunQueueConfigurationGuard
{
    public function ensureSafe(): void
    {
        if (! config('osint.parser_runs.queue.enabled', true)) {
            return;
        }

        $connection = (string) config('queue.default', 'database');
        $driver = (string) config("queue.connections.{$connection}.driver", '');

        if ($driver !== 'redis') {
            return;
        }

        $retryAfter = (int) config("queue.connections.{$connection}.retry_after", 0);

        if ($retryAfter > ProcessParserRun::TIMEOUT_SECONDS) {
            return;
        }

        throw new LogicException(sprintf(
            'Redis queue retry_after (%d) must be greater than parser job timeout (%d).',
            $retryAfter,
            ProcessParserRun::TIMEOUT_SECONDS,
        ));
    }
}
