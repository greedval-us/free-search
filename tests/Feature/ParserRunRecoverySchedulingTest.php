<?php

namespace Tests\Feature;

use App\Jobs\ProcessParserRun;
use App\Models\ParserRun;
use App\Models\User;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ParserRunRecoverySchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_scheduler_crash_leaves_a_short_lease_that_expires_without_clearing_live_locks(): void
    {
        $this->prepareRecovery();
        Artisan::call('list');
        $event = collect(app(Schedule::class)->events())->first(
            static fn (Event $event): bool => str_contains($event->command ?? '', 'app:recover-parser-runs'),
        );
        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('* * * * *', $event->expression);
        $this->assertSame(5, $event->expiresAt);
        $this->assertFalse($event->shouldSkipDueToOverlapping());

        // Simulate a killed scheduler: omit event finish(), leaving its public mutex lease.
        $this->travel(299)->seconds();
        $this->assertTrue($event->shouldSkipDueToOverlapping());
        $this->travel(1)->seconds();
        $this->assertFalse($event->shouldSkipDueToOverlapping());
        $this->assertTrue($event->shouldSkipDueToOverlapping());
        Queue::assertNothingPushed();
    }

    public function test_a_busy_stable_writer_is_skipped_promptly_without_changes_and_retried_after_release(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);
        $this->travel(151)->seconds();
        $path = Storage::disk('private')->path("telegram-parser-runs/{$user->id}/.lock");
        $writer = new Process([PHP_BINARY, '-r',
            '$lock = fopen($argv[1], "c"); flock($lock, LOCK_EX); echo "READY\n"; flush(); usleep(2000000); fclose($lock);', $path,
        ]);
        $writer->setTimeout(5);
        $writer->start();

        try {
            $this->assertTrue($writer->waitUntil(static fn (string $type, string $output): bool => str_contains($output, 'READY')));
            $started = hrtime(true);
            $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
            $this->assertStringContainsString('writer_busy', Artisan::output());
            $this->assertLessThan(1000000000, hrtime(true) - $started);
            $this->assertSame($run, $store->get($user->id, $run['runId']));
            $this->assertDatabaseCount('feature_usage_daily', 0);
            Queue::assertNothingPushed();
        } finally {
            $writer->stop();
        }

        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
        Queue::assertPushed(ProcessParserRun::class, 1);
    }

    public function test_a_bounded_pass_resumes_after_the_last_candidate_and_wraps_to_a_previously_busy_run(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $runs = array_map(fn (): array => $store->create($user->id, []), range(1, 3));
        $this->travel(151)->seconds();
        $busy = Cache::lock("parser-run:advance:telegram:{$user->id}:{$runs[0]['runId']}", 150);
        $this->assertTrue($busy->get());
        $realDispatcher = app(ParserRunJobDispatcherInterface::class);
        $dispatcher = $this->createMock(ParserRunJobDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (string $module, int $userId, string $runId) use ($realDispatcher): void {
                $realDispatcher->dispatch($module, $userId, $runId);
                usleep(1100000); // Complete one already-admitted candidate past the one-second pass budget.
            },
        );
        app()->instance(ParserRunJobDispatcherInterface::class, $dispatcher);

        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
        $this->assertStringContainsString('time budget reached', Artisan::output());
        Queue::assertPushed(ProcessParserRun::class, 1);
        Queue::assertPushed(ProcessParserRun::class, fn (ProcessParserRun $job): bool => $job->runId === $runs[1]['runId']);
        $late = $store->create($user->id, []);
        ParserRun::query()->where('run_id', $late['runId'])->update(['last_activity_at' => now()->subHour()]);
        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
        Queue::assertPushed(ProcessParserRun::class, 2);
        Queue::assertPushed(ProcessParserRun::class, fn (ProcessParserRun $job): bool => $job->runId === $runs[2]['runId']);

        $busy->release();
        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
        Queue::assertPushed(ProcessParserRun::class, 3);
        Queue::assertPushed(ProcessParserRun::class, fn (ProcessParserRun $job): bool => $job->runId === $runs[0]['runId']);
        foreach ($runs as $run) {
            $this->assertSame($run, $store->get($user->id, $run['runId']));
        }
    }

    public function test_a_slow_failed_candidate_does_not_starve_the_next_pass(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $first = $store->create($user->id, []);
        $second = $store->create($user->id, []);
        $this->travel(151)->seconds();
        $realDispatcher = app(ParserRunJobDispatcherInterface::class);
        $dispatcher = $this->createMock(ParserRunJobDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (string $module, int $userId, string $runId) use ($realDispatcher, $first): void {
                if ($runId === $first['runId']) {
                    usleep(1100000);
                    throw new RuntimeException('Queue unavailable for this candidate');
                }
                $realDispatcher->dispatch($module, $userId, $runId);
            },
        );
        app()->instance(ParserRunJobDispatcherInterface::class, $dispatcher);

        $this->assertSame(1, Artisan::call('app:recover-parser-runs'));
        Queue::assertNothingPushed();
        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
        Queue::assertPushed(ProcessParserRun::class, 1);
        Queue::assertPushed(ProcessParserRun::class, fn (ProcessParserRun $job): bool => $job->runId === $second['runId']);
    }

    public function test_dry_run_does_not_move_the_recovery_resume_position(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);
        $this->travel(151)->seconds();
        $before = Cache::getStore()->all();

        $this->assertSame(0, Artisan::call('app:recover-parser-runs', ['--dry-run' => true]));

        $this->assertSame($before, Cache::getStore()->all());
        $this->assertSame($run, $store->get($user->id, $run['runId']));
        Queue::assertNothingPushed();
    }

    private function prepareRecovery(): void
    {
        Storage::fake('private');
        Queue::fake([ProcessParserRun::class]);
        $this->freezeTime();
        config()->set('osint.parser_runs.queue.enabled', true);
        config()->set('osint.parser_runs.recovery.batch_size', 1);
        config()->set('osint.parser_runs.recovery.max_pass_seconds', 1);
        config()->set('queue.default', 'database');
        app()->forgetInstance(ParserRunConfig::class);
        app()->forgetInstance(ParserRunJobDispatcherInterface::class);
    }
}
