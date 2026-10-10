<?php

namespace Tests\Feature;

use App\Jobs\ProcessParserRun;
use App\Models\ParserRun;
use App\Models\User;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use Illuminate\Bus\UniqueLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ParserRunRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_recovers_stale_checkpoints_without_http_and_preserves_the_saved_budget(): void
    {
        $this->prepareRecovery();
        config()->set('osint.parser_runs.recovery.batch_size', 1);
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $first = $store->create($user->id, ['chatUsername' => 'example']);
        $second = $store->create($user->id, ['chatUsername' => 'second']);
        $this->assertTrue((new UniqueLock(Cache::store()))->acquire(new ProcessParserRun(
            'telegram', $user->id, $first['runId'], 0, $first['cursor']['stepRetryUntil'],
        )));
        $this->travel(151)->seconds();

        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));

        Queue::assertPushed(ProcessParserRun::class, 2);
        foreach ([$first, $second] as $run) {
            Queue::assertPushed(ProcessParserRun::class, fn (ProcessParserRun $job): bool => $job->runId === $run['runId']
                && $job->checkpointVersion === 0 && $job->retryDeadline === $run['cursor']['stepRetryUntil']);
            $this->assertSame($run, $store->get($user->id, $run['runId']));
        }
        $this->assertDatabaseCount('parser_runs', 2);
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public function test_recovery_skips_a_busy_execution_lock_then_retries_after_release(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $run = app(TelegramParserRunStore::class)->create($user->id, []);
        $this->travel(151)->seconds();
        $lock = Cache::lock("parser-run:advance:telegram:{$user->id}:{$run['runId']}", 150);
        $this->assertTrue($lock->get());

        try {
            $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
            Queue::assertNothingPushed();
        } finally {
            $lock->release();
        }
        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
        Queue::assertPushed(ProcessParserRun::class, 1);
    }

    public function test_dry_run_does_not_change_files_metadata_locks_or_cooldown(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $run = app(TelegramParserRunStore::class)->create($user->id, []);
        $before = ParserRun::query()->firstOrFail()->getAttributes();
        $files = Storage::disk('private')->allFiles();
        $this->travel(3601)->seconds();

        $this->assertSame(0, Artisan::call('app:recover-parser-runs', ['--dry-run' => true]));

        $this->assertSame($before, ParserRun::query()->firstOrFail()->getAttributes());
        $this->assertSame($run, app(TelegramParserRunStore::class)->get($user->id, $run['runId']));
        $this->assertSame($files, Storage::disk('private')->allFiles());
        $this->assertFalse(Cache::has("parser-run:recovery:telegram:{$user->id}:{$run['runId']}:0"));
        Queue::assertNothingPushed();
    }

    public function test_retry_deadline_failure_preserves_partial_snapshot_without_external_calls(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, ['chatUsername' => 'example']);
        $store->mutate($user->id, $run['runId'], static function (array $state): array {
            $state['data']['messages'] = [['id' => 17, 'text' => 'already collected']];

            return $state;
        });
        $this->travel(3601)->seconds();

        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));

        $after = $store->get($user->id, $run['runId']);
        $this->assertSame('failed', $after['status']);
        $this->assertSame([['id' => 17, 'text' => 'already collected']], $after['result']['messages']);
        $this->assertSame($run['cursor']['stepRetryUntil'], $after['cursor']['stepRetryUntil']);
        $this->assertDatabaseHas('parser_runs', ['run_id' => $run['runId'], 'status' => 'failed']);
        Queue::assertNothingPushed();
    }

    public function test_expired_and_terminal_runs_are_never_requeued_by_cli_or_http(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $expired = $store->create($user->id, []);
        ParserRun::query()->where('run_id', $expired['runId'])->update(['expires_at' => now()->subSecond()]);
        foreach (['completed', 'stopped', 'failed'] as $status) {
            $run = $store->create($user->id, []);
            $store->mutate($user->id, $run['runId'], static fn (array $state): array => [...$state, 'status' => $status]);
        }
        $this->travel(151)->seconds();

        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
        app(ParserRunExecutionCoordinator::class)->status($store, $user->id, $expired['runId'], static fn (array $state): array => $state);

        $this->assertSame($expired, $store->get($user->id, $expired['runId']));
        Queue::assertNothingPushed();
    }

    public function test_missing_and_corrupt_checkpoints_are_reported_without_recreating_files(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $missing = $store->create($user->id, []);
        $corrupt = $store->create($user->id, []);
        $missingPath = "telegram-parser-runs/{$user->id}/{$missing['runId']}.json";
        $corruptPath = "telegram-parser-runs/{$user->id}/{$corrupt['runId']}.json";
        Storage::disk('private')->delete($missingPath);
        Storage::disk('private')->put($corruptPath, '{invalid');
        $this->travel(151)->seconds();

        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));

        Storage::disk('private')->assertMissing($missingPath);
        $this->assertSame('{invalid', Storage::disk('private')->get($corruptPath));
        $this->assertDatabaseCount('parser_runs', 2);
        Queue::assertNothingPushed();
    }

    public function test_dispatch_failure_clears_cooldown_and_the_next_recovery_retries(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);
        $this->travel(151)->seconds();
        $dispatcher = $this->createMock(ParserRunJobDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')->willThrowException(new RuntimeException('Queue unavailable'));
        app()->instance(ParserRunJobDispatcherInterface::class, $dispatcher);

        $this->assertSame(1, Artisan::call('app:recover-parser-runs'));
        $this->assertSame($run, $store->get($user->id, $run['runId']));
        Queue::assertNothingPushed();

        app()->forgetInstance(ParserRunJobDispatcherInterface::class);
        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
        Queue::assertPushed(ProcessParserRun::class, 1);
    }

    public function test_reentrant_recovery_cannot_dispatch_the_checkpoint_twice(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $run = app(TelegramParserRunStore::class)->create($user->id, []);
        $this->travel(151)->seconds();
        $realDispatcher = app(ParserRunJobDispatcherInterface::class);
        $dispatcher = $this->createMock(ParserRunJobDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')->willReturnCallback(
            function (string $module, int $userId, string $runId) use ($realDispatcher): void {
                $this->assertSame(0, Artisan::call('app:recover-parser-runs'));
                $realDispatcher->dispatch($module, $userId, $runId);
            },
        );
        app()->instance(ParserRunJobDispatcherInterface::class, $dispatcher);

        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));

        Queue::assertPushed(ProcessParserRun::class, 1);
        $this->assertSame($run, app(TelegramParserRunStore::class)->get($user->id, $run['runId']));
    }

    public function test_an_expired_checkpoint_cannot_be_committed_by_an_inflight_step(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static function (array $state) use ($run): array {
                ParserRun::query()->where('run_id', $run['runId'])->update(['expires_at' => now()->subSecond()]);

                return [...$state, 'progress' => 50];
            },
        );

        $this->assertNull($after);
        $saved = $store->get($user->id, $run['runId']);
        $this->assertSame($run['data'], $saved['data']);
        $this->assertSame($run['cursor'], $saved['cursor']);
        $this->assertSame(1, $saved['resources']['stepAttempts']);
        $this->assertDatabaseHas('parser_runs', ['run_id' => $run['runId'], 'progress' => 1]);
        Queue::assertNothingPushed();
    }

    public function test_cleanup_during_a_step_does_not_recreate_deleted_checkpoint_or_metadata(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            function (array $state) use ($run): array {
                ParserRun::query()->where('run_id', $run['runId'])->update(['expires_at' => now()->subSecond()]);
                $this->assertSame(0, Artisan::call('app:cleanup-parser-runs'));

                return [...$state, 'progress' => 50];
            },
        );

        $this->assertNull($after);
        $this->assertNull($store->get($user->id, $run['runId']));
        $this->assertDatabaseMissing('parser_runs', ['run_id' => $run['runId']]);
        Queue::assertNothingPushed();
    }

    public function test_recovery_rejects_inline_queue_execution_without_collecting_or_changing_the_checkpoint(): void
    {
        $this->prepareRecovery();
        $user = User::factory()->create();
        $run = app(TelegramParserRunStore::class)->create($user->id, []);
        $this->travel(151)->seconds();
        config()->set('queue.default', 'sync');

        $this->assertSame(1, Artisan::call('app:recover-parser-runs'));

        $this->assertSame($run, app(TelegramParserRunStore::class)->get($user->id, $run['runId']));
        Queue::assertNothingPushed();
    }

    public function test_the_candidate_scan_does_not_include_runs_created_after_its_upper_key(): void
    {
        $this->prepareRecovery();
        config()->set('osint.parser_runs.recovery.batch_size', 1);
        $user = User::factory()->create();
        $newUser = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);
        $this->travel(151)->seconds();
        $realDispatcher = app(ParserRunJobDispatcherInterface::class);
        $dispatcher = $this->createMock(ParserRunJobDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')->willReturnCallback(
            static function (string $module, int $userId, string $runId) use ($realDispatcher, $store, $newUser): void {
                $newRun = $store->create($newUser->id, []);
                ParserRun::query()->where('run_id', $newRun['runId'])->update(['last_activity_at' => now()->subHour()]);
                $realDispatcher->dispatch($module, $userId, $runId);
            },
        );
        app()->instance(ParserRunJobDispatcherInterface::class, $dispatcher);

        $this->assertSame(0, Artisan::call('app:recover-parser-runs'));

        Queue::assertPushed(ProcessParserRun::class, 1);
        Queue::assertPushed(ProcessParserRun::class, fn (ProcessParserRun $job): bool => $job->runId === $run['runId']);
        $this->assertDatabaseCount('parser_runs', 2);
    }

    private function prepareRecovery(): void
    {
        Storage::fake('private');
        Queue::fake([ProcessParserRun::class]);
        $this->freezeTime();
        config()->set('osint.parser_runs.queue.enabled', true);
        config()->set('queue.default', 'database');
        app()->forgetInstance(ParserRunConfig::class);
        app()->forgetInstance(ParserRunJobDispatcherInterface::class);
    }
}
