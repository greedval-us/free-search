<?php

namespace Tests\Unit;

use App\Modules\ParserSupport\ParserRunStateMachine;
use PHPUnit\Framework\TestCase;

final class ParserRunStateMachineTest extends TestCase
{
    public function test_stop_transitions_running_run_to_stopped_and_builds_snapshot(): void
    {
        $stateMachine = new ParserRunStateMachine();
        $snapshot = ['items' => [1, 2, 3]];

        $state = $stateMachine->stop([
            'status' => 'running',
            'stage' => 'messages',
            'error' => null,
        ], fn (array $current): array => $snapshot + ['stage' => $current['stage']]);

        self::assertSame('stopped', $state['status']);
        self::assertSame('stopped', $state['stage']);
        self::assertNull($state['error']);
        self::assertSame($snapshot + ['stage' => 'messages'], $state['result']);
    }

    public function test_stop_does_not_overwrite_failed_state_or_clear_error(): void
    {
        $stateMachine = new ParserRunStateMachine();

        $state = $stateMachine->stop([
            'status' => 'failed',
            'stage' => 'failed',
            'error' => 'Upstream API rate limited the request.',
        ], fn (): array => ['shouldNot' => 'run']);

        self::assertSame('failed', $state['status']);
        self::assertSame('failed', $state['stage']);
        self::assertSame(
            'Upstream API rate limited the request.',
            $state['error']
        );
        self::assertArrayNotHasKey('result', $state);
    }
}
