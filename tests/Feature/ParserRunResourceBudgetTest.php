<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Bluesky\Parser\BlueskyParserRunStore;
use App\Modules\Mastodon\Parser\MastodonParserRunStore;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\ParserSupport\ParserRunGuard;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use App\Modules\YouTube\Parser\YouTubeParserRunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ParserRunResourceBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_duration_limit_stops_before_collection_and_preserves_an_exportable_partial_snapshot(): void
    {
        Storage::fake('private');
        $this->freezeTime();
        config()->set('osint.parser_runs.limits.max_duration_seconds', 300);
        app()->forgetInstance(ParserRunConfig::class);
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);
        $this->travel(300)->seconds();
        $requests = 0;

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static function (array $state) use (&$requests): array {
                $requests++;

                return $state;
            },
            static fn (): array => ['messages' => [['id' => 17]]],
        );

        $this->assertSame('failed', $after['status']);
        $this->assertSame(0, $requests);
        $this->assertSame('duration', $after['resources']['exhausted']);
        $this->assertSame($run['cursor'], $after['cursor']);
        $payload = app(ParserRunGuard::class)->requireDownloadablePayload($after);
        $this->assertSame([['id' => 17]], $payload['messages']);
        $this->assertSame(['status' => 'failed', 'complete' => false], $payload['collection']);
    }

    public function test_transient_failures_spend_durable_attempts_and_a_restarted_coordinator_does_not_reset_them(): void
    {
        Storage::fake('private');
        $this->freezeTime();
        config()->set('osint.parser_runs.queue.enabled', true);
        config()->set('osint.parser_runs.limits.max_step_attempts', 2);
        app()->forgetInstance(ParserRunConfig::class);
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
                    static fn () => throw new RuntimeException('Transient upstream failure'),
                );
                $this->fail('Transient queue failures must still reach the worker.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Transient upstream failure', $exception->getMessage());
            }
            $saved = $store->get($user->id, $run['runId']);
            $this->assertSame($attempt, $saved['resources']['stepAttempts']);
            $this->assertSame($run['cursor'], $saved['cursor']);
            $this->assertSame('running', $saved['status']);
        }

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            fn (): array => $this->fail('The third external step must never run.'), static fn (): array => ['messages' => []],
        );

        $this->assertSame('failed', $after['status']);
        $this->assertSame(2, $after['resources']['stepAttempts']);
        $this->assertSame('step_attempts', $after['resources']['exhausted']);
    }

    public function test_oversized_step_keeps_the_last_saved_data_and_marks_it_incomplete(): void
    {
        Storage::fake('private');
        config()->set('osint.parser_runs.limits.max_checkpoint_bytes', 4096);
        app()->forgetInstance(ParserRunConfig::class);
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static function (array $state): array {
                $state['data']['messages'] = [['id' => 20, 'text' => str_repeat('x', 5000)]];
                $state['status'] = 'completed';

                return $state;
            }, static fn (array $state): array => ['messages' => $state['data']['messages']],
        );

        $this->assertSame('failed', $after['status']);
        $this->assertSame('checkpoint_bytes', $after['resources']['exhausted']);
        $this->assertSame($run['data'], $after['data']);
        $this->assertSame([], $after['result']['messages']);
        $this->assertLessThanOrEqual(4096, strlen(Storage::disk('private')->get("telegram-parser-runs/{$user->id}/{$run['runId']}.json")));
        $this->assertFalse(app(ParserRunGuard::class)->requireDownloadablePayload($after)['collection']['complete']);
    }

    public function test_collector_dto_cannot_erase_the_durable_attempt_counter(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static function (array $state): array {
                unset($state['resources']);
                $state['progress'] = 50;

                return $state;
            },
        );

        $this->assertSame(1, $after['resources']['stepAttempts']);
        $this->assertSame(1, $after['cursor']['checkpointVersion']);
    }

    public function test_future_steps_do_not_spend_execution_attempts(): void
    {
        Storage::fake('private');
        $this->freezeTime();
        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, []);
        $store->mutate($user->id, $run['runId'], static function (array $state): array {
            $state['cursor']['nextAdvanceAt'] = now()->timestamp + 30;

            return $state;
        });
        $before = $store->get($user->id, $run['runId']);

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            fn (): array => $this->fail('A future step must not collect or spend attempts.'),
        );

        $this->assertSame($before, $after);
    }

    #[DataProvider('moduleRecordCounters')]
    public function test_record_boundary_accepts_the_limit_and_rejects_the_next_page_without_losing_saved_data(string $storeClass, array $stats): void
    {
        Storage::fake('private');
        $this->freezeTime();
        config()->set('osint.parser_runs.limits.max_records', 2);
        app()->forgetInstance(ParserRunConfig::class);
        $user = User::factory()->create();
        $store = app($storeClass);
        $run = $store->create($user->id, []);
        $snapshot = static fn (array $state): array => ['records' => $state['data']['savedRecords'] ?? []];
        $atLimit = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static fn (array $state): array => [...$state, 'stats' => $stats, 'data' => ['savedRecords' => ['one', 'two']]], $snapshot,
        );
        $this->assertSame('running', $atLimit['status']);
        $this->travel(2)->seconds();
        $overLimit = $stats;
        $overLimit[array_key_first($overLimit)]++;

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static function (array $state) use ($overLimit): array {
                unset($state['resources']);

                return [...$state, 'stats' => $overLimit, 'data' => ['savedRecords' => ['one', 'two', 'three']], 'status' => 'completed'];
            }, $snapshot,
        );

        $this->assertSame('failed', $after['status']);
        $this->assertSame('records', $after['resources']['exhausted']);
        $this->assertSame(2, $after['resources']['stepAttempts']);
        $this->assertSame($stats, $after['stats']);
        $this->assertSame(['one', 'two'], $after['result']['records']);
        $this->assertFalse(app(ParserRunGuard::class)->requireDownloadablePayload($after)['collection']['complete']);
    }

    public static function moduleRecordCounters(): array
    {
        return [
            'telegram' => [TelegramParserRunStore::class, ['processedMessages' => 1, 'processedComments' => 1]],
            'youtube' => [YouTubeParserRunStore::class, ['processedComments' => 1, 'processedReplies' => 1]],
            'mastodon' => [MastodonParserRunStore::class, ['processedStatuses' => 1, 'processedComments' => 1]],
            'bluesky' => [BlueskyParserRunStore::class, ['processedPosts' => 1, 'processedAuthoredReplies' => 0, 'processedReceivedReplies' => 0,
                'processedFollowers' => 1, 'processedFollows' => 0, 'processedReactions' => 0]],
        ];
    }
}
