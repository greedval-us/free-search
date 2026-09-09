<?php

namespace Tests\Feature;

use App\Jobs\ProcessParserRun;
use App\Models\ParserRun;
use App\Models\User;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ParserStartReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Queue::fake();
        config()->set('osint.parser_runs.queue.enabled', true);
        $limits = config('access.plans.free');
        foreach (array_keys(self::parsers()) as $module) {
            $limits[$module.'.parser'] = 1;
        }
        config()->set('access.plans.free', $limits);
        app()->forgetInstance(ParserRunConfig::class);
        app()->forgetInstance(ParserRunJobDispatcherInterface::class);
    }

    public static function parsers(): array
    {
        return [
            'telegram' => ['telegram', ['chatUsername' => 'example', 'period' => 'week']],
            'youtube' => ['youtube', ['videoId' => 'video123']],
            'mastodon' => ['mastodon', ['account' => '@example@mastodon.social']],
            'bluesky' => ['bluesky', ['actor' => 'example.bsky.social']],
        ];
    }

    #[DataProvider('parsers')]
    public function test_repeated_start_uses_one_quota_and_results_remain_accessible(string $module, array $input): void
    {
        $user = User::factory()->create();
        $first = $this->actingAs($user)->postJson(route($module.'.parser.start'), $input)->assertOk();
        $runId = $first->json('runId');
        $this->assertIsString($runId);
        $this->postJson(route($module.'.parser.start'), $input)
            ->assertOk()->assertJsonPath('runId', $runId);
        Queue::assertPushed(ProcessParserRun::class, 1);
        $this->assertUsage($user, $module, 1);

        $this->get('/'.$module.'?tab=parser')->assertOk();
        $this->getJson(route($module.'.parser.history'))->assertOk()->assertJsonPath('items.0.runId', $runId);
        $this->getJson(route($module.'.parser.status', ['runId' => $runId]))->assertOk();
        $this->postJson(route($module.'.parser.stop', ['runId' => $runId]))->assertOk();

        $this->postJson(route($module.'.parser.start'), $input)
            ->assertStatus(429)->assertJsonPath('meta.used', 1)->assertJsonPath('meta.feature', $module.'.parser');
        $this->assertUsage($user, $module, 1);
        Queue::assertPushed(ProcessParserRun::class, 1);

        $other = User::factory()->create();
        $this->actingAs($other)->getJson(route($module.'.parser.status', ['runId' => $runId]))->assertNotFound();
        $this->postJson(route($module.'.parser.start'), $input)->assertOk();
        Queue::assertPushed(ProcessParserRun::class, 2);
    }

    #[DataProvider('parsers')]
    public function test_dispatch_failure_marks_run_failed_refunds_quota_and_allows_retry(string $module, array $input): void
    {
        $attempts = 0;
        $dispatcher = $this->createMock(ParserRunJobDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(
            function (string $module, int $userId, string $runId) use (&$attempts): void {
                if (++$attempts === 1) {
                    throw new RuntimeException('Queue connection failed');
                }
                Queue::push(new ProcessParserRun($module, $userId, $runId));
            },
        );
        app()->instance(ParserRunJobDispatcherInterface::class, $dispatcher);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route($module.'.parser.start'), $input)->assertStatus(500);
        $failed = ParserRun::query()->sole();
        $this->assertSame('failed', $failed->status);
        $this->assertUsage($user, $module, 0);
        $state = json_decode(Storage::disk('private')->get("{$module}-parser-runs/{$user->id}/{$failed->run_id}.json"), true);
        $this->assertSame('failed', $state['status']);
        $this->assertStringNotContainsString('Queue connection failed', $state['error']);

        $response = $this->postJson(route($module.'.parser.start'), $input)->assertOk();
        $this->assertIsString($response->json('runId'));
        $this->assertNotSame($failed->run_id, $response->json('runId'));
        $this->assertUsage($user, $module, 1);
        Queue::assertPushed(ProcessParserRun::class, 1);
    }

    public function test_storage_failure_refunds_quota_without_dispatching(): void
    {
        $user = User::factory()->create();
        $store = $this->createStub(TelegramParserRunStore::class);
        $store->method('create')->willThrowException(new RuntimeException('Storage unavailable'));
        try {
            app(ParserRunExecutionCoordinator::class)->start($store, 'telegram', $user->id, []);
            $this->fail('Expected storage failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Storage unavailable', $exception->getMessage());
        }
        $this->assertUsage($user, 'telegram', 0);
        Queue::assertNothingPushed();
    }

    public function test_disabled_plan_still_blocks_start(): void
    {
        config()->set('access.plans.free', [...config('access.plans.free'), 'telegram.parser' => 0]);
        $this->actingAs(User::factory()->create())
            ->postJson(route('telegram.parser.start'), ['chatUsername' => 'example', 'period' => 'week'])
            ->assertForbidden();
        Queue::assertNothingPushed();
    }

    private function assertUsage(User $user, string $module, int $used): void
    {
        $this->assertDatabaseHas('feature_usage_daily', [
            'user_id' => $user->id, 'feature' => $module.'.parser', 'used' => $used,
        ]);
    }
}
