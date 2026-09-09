<?php

namespace Tests\Unit;

use App\Jobs\ProcessParserRun;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunQueueConfigurationGuard;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ParserRunQueueConfigurationGuardTest extends TestCase
{
    #[DataProvider('queueDrivers')]
    public function test_all_visibility_timeout_drivers_are_validated(string $driver): void
    {
        config()->set('queue.default', $driver);
        config()->set("queue.connections.{$driver}.retry_after", 90);
        $this->expectException(LogicException::class);
        (new ParserRunQueueConfigurationGuard(new ParserRunConfig(true, 'default', 2)))->ensureSafe();
    }

    public static function queueDrivers(): array
    {
        return [['database'], ['beanstalkd'], ['redis']];
    }

    public function test_redis_retry_window_must_exceed_parser_job_timeout(): void
    {
        config()->set('osint.parser_runs.queue.enabled', true);
        config()->set('queue.default', 'redis');
        config()->set('queue.connections.redis.driver', 'redis');
        config()->set('queue.connections.redis.retry_after', ProcessParserRun::TIMEOUT_SECONDS);

        $this->expectException(LogicException::class);

        (new ParserRunQueueConfigurationGuard($this->parserRunConfig()))->ensureSafe();
    }

    public function test_safe_redis_retry_window_is_accepted(): void
    {
        config()->set('osint.parser_runs.queue.enabled', true);
        config()->set('queue.default', 'redis');
        config()->set('queue.connections.redis.driver', 'redis');
        config()->set('queue.connections.redis.retry_after', ProcessParserRun::TIMEOUT_SECONDS + 30);

        (new ParserRunQueueConfigurationGuard($this->parserRunConfig()))->ensureSafe();

        $this->addToAssertionCount(1);
    }

    private function parserRunConfig(): ParserRunConfig
    {
        return ParserRunConfig::fromArray((array) config('osint.parser_runs', []));
    }
}
