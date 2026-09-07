<?php

namespace Tests\Feature;

use App\Console\Commands\CleanupParserRunFiles;
use App\Models\ParserRun;
use App\Models\User;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Tests\TestCase;

class ParserRunStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_parser_run_metadata_is_saved_without_payload_in_database(): void
    {
        Storage::fake('private');

        $user = User::factory()->create();

        $run = app(TelegramParserRunStore::class)->create($user->id, [
            'query' => 'osint',
            'limit' => 50,
        ]);

        $metadata = ParserRun::query()->where('run_id', $run['runId'])->firstOrFail();

        $this->assertSame($user->id, $metadata->user_id);
        $this->assertSame('telegram', $metadata->module);
        $this->assertSame('running', $metadata->status);
        $this->assertSame('messages', $metadata->stage);
        $this->assertSame(1, $metadata->progress);
        $this->assertStringEndsWith('.json', $metadata->file_path);
        $this->assertNotNull($metadata->file_size_bytes);
        $this->assertNull($metadata->finished_at);
        $this->assertNotNull($metadata->expires_at);

        $this->assertDatabaseCount('parser_runs', 1);
        $this->assertDatabaseMissing('parser_runs', [
            'run_id' => $run['runId'],
            'error' => json_encode($run['context']),
        ]);
        Storage::disk('private')->assertExists($metadata->file_path);
    }

    public function test_cleanup_command_removes_expired_metadata_and_json_files(): void
    {
        Storage::fake('private');
        Log::spy();

        config()->set('osint.parser_runs.retention_days', 7);
        config()->set('osint.parser_runs.cleanup_batch_size', 1);

        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $firstRun = $store->create($user->id, ['query' => 'cleanup-first']);
        $secondRun = $store->create($user->id, ['query' => 'cleanup-second']);

        $metadatas = ParserRun::query()
            ->whereIn('run_id', [$firstRun['runId'], $secondRun['runId']])
            ->get();

        foreach ($metadatas as $metadata) {
            $metadata->forceFill([
            'expires_at' => CarbonImmutable::now()->subDay(),
            ])->save();

            Storage::disk('private')->assertExists($metadata->file_path);
        }

        Artisan::call(CleanupParserRunFiles::class);

        $this->assertDatabaseMissing('parser_runs', [
            'run_id' => $firstRun['runId'],
        ]);
        $this->assertDatabaseMissing('parser_runs', [
            'run_id' => $secondRun['runId'],
        ]);

        foreach ($metadatas as $metadata) {
            Storage::disk('private')->assertMissing($metadata->file_path);
        }

        Log::shouldHaveReceived('info')->once();
    }

    public function test_cleanup_command_dry_run_reports_matches_without_deleting_data(): void
    {
        Storage::fake('private');
        Log::spy();

        config()->set('osint.parser_runs.cleanup_batch_size', 1);

        $user = User::factory()->create();
        $run = app(TelegramParserRunStore::class)->create($user->id, ['query' => 'dry-run']);

        $metadata = ParserRun::query()->where('run_id', $run['runId'])->firstOrFail();
        $metadata->forceFill([
            'expires_at' => CarbonImmutable::now()->subDay(),
        ])->save();

        $exitCode = Artisan::call(CleanupParserRunFiles::class, ['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('parser_runs', [
            'run_id' => $run['runId'],
        ]);
        Storage::disk('private')->assertExists($metadata->file_path);
        Log::shouldHaveReceived('info')->once();
    }

    public function test_corrupted_run_file_is_not_overwritten_during_read_or_mutation(): void
    {
        Storage::fake('private');

        $user = User::factory()->create();
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($user->id, ['query' => 'corruption-check']);
        $metadata = ParserRun::query()->where('run_id', $run['runId'])->firstOrFail();
        $corruptedJson = '{invalid-json';

        Storage::disk('private')->put($metadata->file_path, $corruptedJson);
        Log::spy();

        $this->assertNull($store->get($user->id, $run['runId']));
        Log::shouldHaveReceived('warning')->once();

        try {
            $store->mutate($user->id, $run['runId'], static fn (array $state): array => $state);
            $this->fail('Mutating corrupted parser state must fail.');
        } catch (JsonException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame($corruptedJson, Storage::disk('private')->get($metadata->file_path));
    }
}
