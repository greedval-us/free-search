<?php

namespace Tests\Unit;

use App\Jobs\ProcessParserRun;
use App\Modules\ParserSupport\Contracts\ParserRunBackgroundProcessorInterface;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunBackgroundProcessorRegistry;
use App\Modules\ParserSupport\ParserRunExecutionConfig;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use PHPUnit\Framework\TestCase;

class ProcessParserRunTest extends TestCase
{
    public function test_it_schedules_next_step_while_run_is_active(): void
    {
        $processor = $this->createMock(ParserRunBackgroundProcessorInterface::class);
        $processor->expects($this->once())->method('moduleKey')->willReturn('telegram');
        $processor->expects($this->once())
            ->method('advanceRun')
            ->with(10, 'run-id')
            ->willReturn(true);

        $dispatcher = $this->createMock(ParserRunJobDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with('telegram', 10, 'run-id', 2);

        $job = new ProcessParserRun('telegram', 10, 'run-id');
        $job->handle(
            new ParserRunBackgroundProcessorRegistry([$processor]),
            $dispatcher,
            new ParserRunExecutionConfig(true, 'parser-runs', 2),
        );
    }

    public function test_it_does_not_schedule_next_step_after_completion(): void
    {
        $processor = $this->createMock(ParserRunBackgroundProcessorInterface::class);
        $processor->expects($this->once())->method('moduleKey')->willReturn('youtube');
        $processor->expects($this->once())
            ->method('advanceRun')
            ->with(20, 'finished-run')
            ->willReturn(false);

        $dispatcher = $this->createMock(ParserRunJobDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $job = new ProcessParserRun('youtube', 20, 'finished-run');
        $job->handle(
            new ParserRunBackgroundProcessorRegistry([$processor]),
            $dispatcher,
            new ParserRunExecutionConfig(true, 'parser-runs', 2),
        );
    }

    public function test_telegram_jobs_use_module_lock_for_single_session_safety(): void
    {
        $middleware = (new ProcessParserRun('telegram', 10, 'run-id'))->middleware();

        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[0]);
    }

    public function test_non_telegram_jobs_can_run_in_parallel(): void
    {
        $this->assertSame([], (new ProcessParserRun('youtube', 10, 'run-id'))->middleware());
    }
}
