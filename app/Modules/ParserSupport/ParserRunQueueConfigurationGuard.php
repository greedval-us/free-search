<?php

namespace App\Modules\ParserSupport;

use App\Jobs\ProcessParserRun;
use LogicException;

final class ParserRunQueueConfigurationGuard
{
    public function __construct(
        private readonly ParserRunConfig $config,
    ) {}

    public function ensureSafe(): void
    {
        if (! $this->config->queueEnabled()) {
            return;
        }

        $connection = (string) config('queue.default', 'database');
        $driver = (string) config("queue.connections.{$connection}.driver", '');

        if (! in_array($driver, ['redis', 'database', 'beanstalkd'], true)) {
            return;
        }

        $retryAfter = (int) config("queue.connections.{$connection}.retry_after", 0);

        if ($retryAfter > ProcessParserRun::TIMEOUT_SECONDS) {
            return;
        }

        throw new LogicException(sprintf(
            'Queue connection %s retry_after (%d) must be greater than parser job timeout (%d).',
            $connection,
            $retryAfter,
            ProcessParserRun::TIMEOUT_SECONDS,
        ));
    }
}
