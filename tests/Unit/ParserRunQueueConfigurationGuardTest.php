<?php

namespace Tests\Unit;

use App\Jobs\ProcessParserRun;
use App\Modules\ParserSupport\ParserRunQueueConfigurationGuard;
use LogicException;
use Tests\TestCase;

class ParserRunQueueConfigurationGuardTest extends TestCase
{
    public function test_redis_retry_window_must_exceed_parser_job_timeout(): void
    {
        config()->set('osint.parser_runs.queue.enabled', true);
        config()->set('queue.default', 'redis');
        config()->set('queue.connections.redis.driver', 'redis');
        config()->set('queue.connections.redis.retry_after', ProcessParserRun::TIMEOUT_SECONDS);

        $this->expectException(LogicException::class);

        (new ParserRunQueueConfigurationGuard)->ensureSafe();
    }

    public function test_safe_redis_retry_window_is_accepted(): void
    {
        config()->set('osint.parser_runs.queue.enabled', true);
        config()->set('queue.default', 'redis');
        config()->set('queue.connections.redis.driver', 'redis');
        config()->set('queue.connections.redis.retry_after', ProcessParserRun::TIMEOUT_SECONDS + 30);

        (new ParserRunQueueConfigurationGuard)->ensureSafe();

        $this->addToAssertionCount(1);
    }
}
