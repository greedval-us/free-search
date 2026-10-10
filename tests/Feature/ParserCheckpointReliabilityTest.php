<?php

namespace Tests\Feature;

use App\Jobs\ProcessParserRun;
use App\Models\ParserRun;
use App\Models\User;
use App\Modules\Bluesky\Parser\BlueskyParserRunStore;
use App\Modules\Mastodon\Parser\MastodonParserRunStore;
use App\Modules\ParserSupport\Contracts\ParserRunBackgroundProcessorInterface;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunBackgroundProcessorRegistry;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Response\Info\ChannelInfoDTO;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Parser\YouTubeParserCollector;
use App\Modules\YouTube\Parser\YouTubeParserRunStore;
use Illuminate\Bus\UniqueLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ParserCheckpointReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stale_job_cannot_continue_when_a_newer_checkpoint_holds_the_execution_lock(): void
    {
        Storage::fake('private');
        $this->configureQueue(true);
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, ['chatUsername' => 'example']);
        $store->mutate($user->id, $run['runId'], static function (array $state): array {
            $state['cursor']['checkpointVersion'] = 1;

            return $state;
        });
        $lock = Cache::lock("parser-run:advance:telegram:{$user->id}:{$run['runId']}", 150);
        $this->assertTrue($lock->get());
        $before = $store->get($user->id, $run['runId']);

        try {
            $this->assertNull(app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
                fn (): array => $this->fail('A stale job must not collect another page.'), null, 0,
            ));
            $this->assertSame($before, $store->get($user->id, $run['runId']));
        } finally {
            $lock->release();
        }
    }

    public function test_transient_background_exception_preserves_checkpoint_for_retry(): void
    {
        Storage::fake('private');
        $this->configureQueue(true);
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, ['chatUsername' => 'example']);

        try {
            app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
                static fn () => throw new RuntimeException('temporary failure'),
            );
            $this->fail('A queue exception must reach the worker for retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('temporary failure', $exception->getMessage());
        }

        $saved = $store->get($user->id, $run['runId']);
        $this->assertSame(1, $saved['resources']['stepAttempts']);
        unset($saved['resources'], $saved['updatedAt'], $run['updatedAt']);
        $this->assertSame($run, $saved);
        $this->assertDatabaseHas('parser_runs', ['run_id' => $run['runId'], 'status' => 'running', 'progress' => 1]);
        $continued = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static fn (array $state): array => [...$state, 'progress' => 25],
        );
        $this->assertSame(25, $continued['progress']);
    }

    public function test_stop_during_external_call_wins_over_late_checkpoint(): void
    {
        Storage::fake('private');
        $this->configureQueue(false);
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, ['chatUsername' => 'example']);
        $coordinator = app(ParserRunExecutionCoordinator::class);

        $after = $coordinator->advance($store, $user->id, $run['runId'],
            function (array $state) use ($coordinator, $store, $user, $run): array {
                $stopped = $coordinator->stop($store, $user->id, $run['runId'], static fn (): array => ['messages' => []]);
                $this->assertSame('stopped', $stopped['status']);

                return [...$state, 'status' => 'completed', 'progress' => 100, 'result' => ['messages' => ['late']]];
            },
        );

        $this->assertSame('stopped', $after['status']);
        $this->assertSame(['messages' => []], $after['result']);
        $this->assertDatabaseHas('parser_runs', ['run_id' => $run['runId'], 'status' => 'stopped']);
    }

    public function test_inline_failure_exports_previous_partial_checkpoint_without_raw_error(): void
    {
        Storage::fake('private');
        $this->configureQueue(false);
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, ['chatUsername' => 'example']);
        $store->mutate($user->id, $run['runId'], static function (array $state): array {
            $state['data']['messages'] = [['id' => 10]];

            return $state;
        });

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static fn () => throw new RuntimeException('secret=private-key'),
            static fn (array $state): array => ['messages' => $state['data']['messages']],
        );

        $this->assertSame('failed', $after['status']);
        $this->assertSame(1, $after['progress']);
        $this->assertSame([['id' => 10]], $after['result']['messages']);
        $this->assertStringNotContainsString('private-key', $after['error']);
        $this->assertDatabaseHas('parser_runs', ['run_id' => $run['runId'], 'status' => 'failed']);
    }

    public function test_late_failure_callback_preserves_stopped_and_completed_runs(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $coordinator = app(ParserRunExecutionCoordinator::class);

        foreach (['stopped', 'completed'] as $status) {
            $run = $store->create($user->id, ['chatUsername' => 'example']);
            $store->mutate($user->id, $run['runId'], static fn (array $state): array => [...$state, 'status' => $status, 'result' => ['messages' => [['id' => 10]]]]);
            $terminal = $store->get($user->id, $run['runId']);
            $this->travel(5)->seconds();

            $coordinator->fail($store, $user->id, $run['runId'], 'late failure', static fn (): array => ['messages' => []]);

            $after = $store->get($user->id, $run['runId']);
            $this->assertSame($terminal, $after);
            $this->assertSame($status, $after['status']);
            $this->assertSame([['id' => 10]], $after['result']['messages']);
            $this->assertNull($after['error']);
        }
    }

    public function test_stale_running_checkpoint_is_requeued_once_despite_orphaned_unique_lock(): void
    {
        Storage::fake('private');
        Queue::fake([ProcessParserRun::class]);
        $this->configureQueue(true);
        $this->freezeTime();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, ['chatUsername' => 'example']);
        $job = new ProcessParserRun('telegram', $user->id, $run['runId'], 0, $run['cursor']['stepRetryUntil']);
        $this->assertTrue((new UniqueLock(Cache::store()))->acquire($job));
        $this->travel(151)->seconds();
        $coordinator = app(ParserRunExecutionCoordinator::class);

        $first = $coordinator->status($store, $user->id, $run['runId'], static fn (array $state): array => $state);
        $second = $coordinator->status($store, $user->id, $run['runId'], static fn (array $state): array => $state);

        $this->assertSame($run, $first);
        $this->assertSame($first, $second);
        Queue::assertPushed(ProcessParserRun::class, 1);
    }

    public function test_recovery_marks_checkpoint_failed_after_durable_retry_deadline_without_requeue(): void
    {
        Storage::fake('private');
        Queue::fake([ProcessParserRun::class]);
        $this->configureQueue(true);
        $this->freezeTime();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, ['chatUsername' => 'example']);
        $this->travel(3601)->seconds();

        $after = app(ParserRunExecutionCoordinator::class)->status($store, $user->id, $run['runId'],
            static fn (array $state): array => $state,
            static fn (): array => ['messages' => []],
        );

        $this->assertSame('failed', $after['status']);
        $this->assertSame(1, $after['progress']);
        $this->assertSame(['messages' => []], $after['result']);
        $this->assertDatabaseHas('parser_runs', ['run_id' => $run['runId'], 'status' => 'failed']);
        Queue::assertNothingPushed();
    }

    public function test_versioned_worker_reconciles_terminal_dto_checkpoint_after_metadata_commit_failure(): void
    {
        Storage::fake('private');
        $this->freezeTime();
        $this->configureQueue(true);
        $gateway = $this->createMock(YouTubeGatewayInterface::class);
        $gateway->expects($this->never())->method('commentThreads');
        $gateway->expects($this->never())->method('comments');
        app()->instance(YouTubeGatewayInterface::class, $gateway);
        $user = User::factory()->create();
        $store = app(YouTubeParserRunStore::class);
        $run = $store->create($user->id, ['videoId' => 'abc123']);
        $run['stage'] = 'finishing';
        $run['cursor']['checkpointVersion'] = 7;
        $terminal = app(YouTubeParserCollector::class)->advance($run);
        $store->write($user->id, $run['runId'], $terminal);
        ParserRun::query()->where('run_id', $run['runId'])->update(['status' => 'running']);
        $queue = Queue::connection('database');
        $queue->push(new ProcessParserRun('youtube', $user->id, $run['runId'], 7, $run['cursor']['stepRetryUntil']), '', 'test-parser-terminal-reconcile');
        $job = $queue->pop('test-parser-terminal-reconcile');

        app('queue.worker')->process('database', $job, new WorkerOptions);

        $this->assertTrue($job->isDeleted());
        $this->assertSame($terminal, $store->get($user->id, $run['runId']));
        $this->assertDatabaseHas('parser_runs', ['run_id' => $run['runId'], 'status' => 'completed']);
    }

    public function test_dispatcher_reads_each_registered_module_saved_checkpoint_and_retry_budget(): void
    {
        Storage::fake('private');
        Queue::fake([ProcessParserRun::class]);
        $this->freezeTime();
        $this->configureQueue(true);
        $user = User::factory()->create();
        $retryDeadline = now()->timestamp + 90;
        $expected = [];
        foreach ([TelegramParserRunStore::class, YouTubeParserRunStore::class, MastodonParserRunStore::class, BlueskyParserRunStore::class] as $class) {
            $store = app($class);
            $run = $store->create($user->id, []);
            $store->mutate($user->id, $run['runId'], static function (array $state) use ($retryDeadline): array {
                $state['cursor']['checkpointVersion'] = 7;
                $state['cursor']['stepRetryUntil'] = $retryDeadline;

                return $state;
            });
            $expected[$store->module()] = $run['runId'];

            app(ParserRunJobDispatcherInterface::class)->dispatch($store->module(), $user->id, $run['runId']);
        }

        Queue::assertPushed(ProcessParserRun::class, 4);
        foreach ($expected as $module => $runId) {
            Queue::assertPushed(ProcessParserRun::class, static fn (ProcessParserRun $job): bool => $job->module === $module
                && $job->runId === $runId && $job->checkpointVersion === 7 && $job->retryDeadline === $retryDeadline);
        }
    }

    public function test_expired_old_job_cannot_fail_a_newer_recovered_checkpoint_or_release_its_unique_lock(): void
    {
        Storage::fake('private');
        $this->freezeTime();
        config()->set('queue.default', 'database');
        config()->set('osint.parser_runs.queue.name', 'test-parser-generations');
        $this->configureQueue(true);
        $gateway = $this->createMock(TelegramGatewayInterface::class);
        $gateway->expects($this->once())->method('getInfo')->willReturn(new ChannelInfoDTO(['chat' => ['broadcast' => true]]));
        $gateway->expects($this->once())->method('getMessages')->willReturn(new ChannelMessagesDTO(['messages' => [['id' => 100, 'date' => now()->timestamp]]]));
        app()->instance(TelegramGatewayInterface::class, $gateway);
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, ['chatUsername' => 'example']);
        $queue = Queue::connection('database');
        $old = new ProcessParserRun('telegram', $user->id, $run['runId'], 0, $run['cursor']['stepRetryUntil']);
        $unique = new UniqueLock(Cache::store());
        $this->assertTrue($unique->acquire($old));
        $queue->later(3601, $old, '', 'test-parser-generations');
        $this->travel(151)->seconds();
        app(ParserRunExecutionCoordinator::class)->status($store, $user->id, $run['runId'], static fn (array $state): array => $state);
        $worker = app('queue.worker');
        $replacement = $queue->pop('test-parser-generations');

        $worker->process('database', $replacement, new WorkerOptions);
        $newer = $store->get($user->id, $run['runId']);
        $this->assertSame(1, $newer['cursor']['checkpointVersion']);
        $newJob = new ProcessParserRun('telegram', $user->id, $run['runId'], 1, $newer['cursor']['stepRetryUntil']);
        $this->assertFalse($unique->acquire($newJob), 'The next checkpoint already has a queued job.');
        $this->travel(3450)->seconds();
        $expired = $queue->pop('test-parser-generations');
        try {
            $worker->process('database', $expired, new WorkerOptions);
            $this->fail('The old serialized job must have expired.');
        } catch (MaxAttemptsExceededException) {
            $this->assertTrue($expired->hasFailed());
        }

        $this->assertSame($newer, $store->get($user->id, $run['runId']));
        $this->assertFalse($unique->acquire($newJob), 'An old failure must preserve the newer job uniqueness lock.');
        $this->assertDatabaseHas('parser_runs', ['run_id' => $run['runId'], 'status' => 'running']);
        $this->assertNull(app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            fn (): array => $this->fail('An old checkpoint must not make another external request.'), null, 0,
        ));
    }

    public function test_transient_worker_failure_waits_for_backoff_before_retrying(): void
    {
        $this->freezeTime();
        $processor = $this->createMock(ParserRunBackgroundProcessorInterface::class);
        $processor->method('moduleKey')->willReturn('youtube');
        $attempt = 0;
        $processor->expects($this->exactly(2))->method('advanceRun')->willReturnCallback(
            static function () use (&$attempt): bool {
                if (++$attempt === 1) {
                    throw new RuntimeException('Temporary upstream outage.');
                }

                return false;
            },
        );
        app()->instance(ParserRunBackgroundProcessorRegistry::class, new ParserRunBackgroundProcessorRegistry([$processor]));
        $queue = Queue::connection('database');
        $queue->push(new ProcessParserRun('youtube', 10, 'retry-checkpoint', 0, now()->timestamp + 3600), '', 'test-parser-backoff');
        $worker = app('queue.worker');
        $first = $queue->pop('test-parser-backoff');

        try {
            $worker->process('database', $first, new WorkerOptions);
            $this->fail('The first request must fail transiently.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Temporary upstream outage.', $exception->getMessage());
        }

        $this->assertTrue($first->isReleased());
        $this->assertNull($queue->pop('test-parser-backoff'));
        $this->travel(5)->seconds();
        $retry = $queue->pop('test-parser-backoff');
        $this->assertNotNull($retry);
        $worker->process('database', $retry, new WorkerOptions);
        $this->assertTrue($retry->isDeleted());
    }

    private function configureQueue(bool $enabled): void
    {
        config()->set('osint.parser_runs.queue.enabled', $enabled);
        if ($enabled) {
            config()->set('queue.default', 'database');
        }
        app()->forgetInstance(ParserRunConfig::class);
        app()->forgetInstance(ParserRunJobDispatcherInterface::class);
    }
}
