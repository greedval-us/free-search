<?php

namespace Tests\Feature;

use App\Jobs\ProcessParserRun;
use App\Models\User;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunBackgroundProcessorRegistry;
use App\Modules\ParserSupport\ParserRunExecutionConfig;
use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParserRunBackgroundExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_parser_modules_register_background_processors(): void
    {
        $registry = app(ParserRunBackgroundProcessorRegistry::class);

        foreach (['telegram', 'youtube', 'mastodon', 'bluesky'] as $module) {
            $this->assertSame($module, $registry->forModule($module)->moduleKey());
        }
    }

    public function test_start_dispatches_background_job_and_status_is_read_only(): void
    {
        Storage::fake('private');
        Queue::fake();
        $this->enableBackgroundExecution();

        $user = User::factory()->create();
        $service = app(TelegramParserApplicationServiceInterface::class);

        $started = $service->start(new TelegramParserStartDTO(
            userId: $user->id,
            chatUsername: 'example',
            period: 'week',
            keyword: null,
            range: [
                'dateFrom' => null,
                'dateTo' => null,
                'minTimestamp' => null,
                'maxTimestamp' => null,
            ],
        ));

        Queue::assertPushed(
            ProcessParserRun::class,
            fn (ProcessParserRun $job): bool => $job->module === 'telegram'
                && $job->userId === $user->id
                && $job->runId === $started->toArray()['runId'],
        );

        $path = "telegram-parser-runs/{$user->id}/{$started->toArray()['runId']}.json";
        $beforeStatus = Storage::disk('private')->get($path);

        $status = $service->status($user->id, $started->toArray()['runId']);

        $this->assertNotNull($status);
        $this->assertSame($beforeStatus, Storage::disk('private')->get($path));
        $this->assertSame(1, $status->toArray()['progress']);
    }

    public function test_repeated_start_returns_active_run_without_dispatching_duplicate_job(): void
    {
        Storage::fake('private');
        Queue::fake();
        $this->enableBackgroundExecution();

        $user = User::factory()->create();
        $service = app(TelegramParserApplicationServiceInterface::class);
        $input = new TelegramParserStartDTO(
            userId: $user->id,
            chatUsername: 'example',
            period: 'week',
            keyword: null,
            range: [
                'dateFrom' => null,
                'dateTo' => null,
                'minTimestamp' => null,
                'maxTimestamp' => null,
            ],
        );

        $firstRun = $service->start($input);
        $repeatedRun = $service->start($input);

        $this->assertSame($firstRun->toArray()['runId'], $repeatedRun->toArray()['runId']);
        Queue::assertPushed(ProcessParserRun::class, 1);
    }

    private function enableBackgroundExecution(): void
    {
        config()->set('osint.parser_runs.queue.enabled', true);
        config()->set('osint.parser_runs.queue.name', 'parser-runs');
        config()->set('osint.parser_runs.queue.step_delay_seconds', 2);

        app()->forgetInstance(ParserRunExecutionConfig::class);
        app()->forgetInstance(ParserRunJobDispatcherInterface::class);
    }
}
