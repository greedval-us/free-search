<?php

namespace Tests\Feature;

use App\Jobs\ProcessParserRun;
use App\Models\User;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\JsonRunStore;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunStoreRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Tests\Fixtures\Parser\AdditionalParserRunStore;
use Tests\TestCase;

class ParserRunStoreRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_module_can_register_and_queue_its_store_without_a_shared_module_list(): void
    {
        Storage::fake('private');
        Queue::fake();
        $this->freezeTime();
        config()->set('osint.parser_runs.queue.enabled', true);
        app()->forgetInstance(ParserRunConfig::class);
        app()->forgetInstance(ParserRunJobDispatcherInterface::class);
        $registry = app(ParserRunStoreRegistry::class);

        app()->register(new class(app()) extends ServiceProvider
        {
            public function register(): void
            {
                $this->app->tag([AdditionalParserRunStore::class], JsonRunStore::CONTAINER_TAG);
            }
        });
        $store = $registry->forModule('additional-source');
        $this->assertInstanceOf(AdditionalParserRunStore::class, $store);
        $user = User::factory()->create();
        $run = $store->create($user->id, ['query' => 'extension']);
        $deadline = now()->timestamp + 90;
        $store->mutate($user->id, $run['runId'], static function (array $state) use ($deadline): array {
            $state['cursor']['checkpointVersion'] = 7;
            $state['cursor']['stepRetryUntil'] = $deadline;

            return $state;
        });

        app(ParserRunJobDispatcherInterface::class)->dispatch('additional-source', $user->id, $run['runId']);

        Queue::assertPushed(ProcessParserRun::class, static fn (ProcessParserRun $job): bool => $job->module === 'additional-source'
            && $job->userId === $user->id
            && $job->runId === $run['runId']
            && $job->checkpointVersion === 7
            && $job->retryUntil() === $deadline);
        $this->assertDatabaseHas('parser_runs', [
            'run_id' => $run['runId'],
            'module' => 'additional-source',
            'user_id' => $user->id,
        ]);
    }
}
