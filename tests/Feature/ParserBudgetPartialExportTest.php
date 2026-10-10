<?php

namespace Tests\Feature;

use App\Modules\Bluesky\Parser\BlueskyParserRunStore;
use App\Modules\Mastodon\Parser\MastodonParserRunStore;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use App\Modules\YouTube\Parser\YouTubeParserRunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Controllers\Concerns\CreatesPaidUser;
use Tests\TestCase;

class ParserBudgetPartialExportTest extends TestCase
{
    use CreatesPaidUser;
    use RefreshDatabase;

    #[DataProvider('sourceDownloads')]
    public function test_exact_checkpoint_boundary_keeps_partial_downloads_available_without_rewriting_saved_data(
        string $module, string $storeClass, array $context, string $itemsKey, string $textKey, array $item, string $format,
    ): void {
        Storage::fake('private');
        Http::preventStrayRequests();
        $this->freezeTime();
        config()->set('osint.parser_runs.queue.enabled', false);
        config()->set('osint.parser_runs.limits.max_checkpoint_bytes', 4096);
        config()->set('osint.parser_runs.limits.max_step_attempts', 1);
        app()->forgetInstance(ParserRunConfig::class);
        $user = $this->paidUser();
        $store = app($storeClass);
        $run = $store->create($user->id, $context);
        $saved = $store->mutate($user->id, $run['runId'], static function (array $state) use ($itemsKey, $textKey, $item): array {
            $state['data'][$itemsKey] = [$item];
            $state['stats'][array_key_first($state['stats'])] = 1;
            $state['resources']['stepAttempts'] = 1;
            $bytes = strlen(json_encode($state, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $state['data'][$itemsKey][0][$textKey] .= str_repeat('x', 4096 - $bytes);

            return $state;
        });
        $path = "{$module}-parser-runs/{$user->id}/{$run['runId']}.json";
        $this->assertSame(4096, strlen(Storage::disk('private')->get($path)));
        $this->actingAs($user);

        $status = $this->getJson(route("{$module}.parser.status", ['runId' => $run['runId']]))
            ->assertOk()->assertJsonPath('status', 'failed');
        $failed = $store->get($user->id, $run['runId']);
        $this->assertSame('step_attempts', $failed['resources']['exhausted']);
        $this->assertNull($failed['result']);
        $this->assertSame($saved['data'], $failed['data']);
        $checkpointBeforeDownload = Storage::disk('private')->get($path);

        $response = $this->getJson(route("{$module}.parser.download-{$format}", ['runId' => $run['runId'], 'locale' => 'en']))->assertOk();
        $text = $saved['data'][$itemsKey][0][$textKey];
        if ($format === 'json') {
            $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(['status' => 'failed', 'complete' => false], $payload['collection']);
            $this->assertSame($saved['data'][$itemsKey], $payload[$itemsKey]);
        } else {
            $file = $response->baseResponse->getFile()->getPathname();
            try {
                $spreadsheet = IOFactory::load($file);
                $values = [];
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    foreach ($sheet->toArray() as $row) {
                        array_push($values, ...$row);
                    }
                }
                $this->assertContains($text, $values);
                $summary = array_column($spreadsheet->getSheet(0)->toArray(), 1, 0);
                $this->assertSame(__('exports.common.no'), $summary[__('exports.common.collection_complete')]);
                $this->assertSame(__('exports.common.collection_statuses.failed'), $summary[__('exports.common.collection_status')]);
            } finally {
                if (isset($spreadsheet)) {
                    $spreadsheet->disconnectWorksheets();
                }
                @unlink($file);
            }
        }

        $this->assertNotNull($status->json('downloadUrl'));
        $this->assertNotNull($status->json('downloadJsonUrl'));
        $this->getJson(route("{$module}.parser.history"))->assertOk()
            ->assertJsonPath('items.0.downloadable', true)
            ->assertJsonPath('items.0.runId', $run['runId']);
        $this->assertSame($checkpointBeforeDownload, Storage::disk('private')->get($path));
        Http::assertNothingSent();
    }

    public static function sourceDownloads(): array
    {
        $sources = [
            'telegram' => [TelegramParserRunStore::class, ['chatUsername' => 'public_test'], 'messages', 'message', ['id' => 17, 'message' => 'saved-telegram ', 'date' => 1700000000]],
            'youtube' => [YouTubeParserRunStore::class, ['videoId' => 'test-video'], 'commentsIndex', 'text', ['commentId' => 'saved-comment', 'threadId' => 'saved-thread', 'text' => 'saved-youtube ']],
            'mastodon' => [MastodonParserRunStore::class, ['account' => 'alice@example.social'], 'statusesIndex', 'content', ['id' => 'saved-status', 'content' => 'saved-mastodon ']],
            'bluesky' => [BlueskyParserRunStore::class, ['actor' => 'alice.test'], 'postsIndex', 'text', ['uri' => 'at://did:plc:test/app.bsky.feed.post/17', 'text' => 'saved-bluesky ']],
        ];
        $cases = [];
        foreach ($sources as $module => $source) {
            foreach (['json', 'excel'] as $format) {
                $cases["{$module} {$format}"] = [$module, ...$source, $format];
            }
        }

        return $cases;
    }

    #[DataProvider('budgetReasons')]
    public function test_previously_saved_budget_failures_can_export_without_a_new_checkpoint_marker(string $reason): void
    {
        [$store, $run] = $this->savedYouTubeRun(['resources' => ['exhausted' => $reason]]);
        $before = $store->get($run['userId'], $run['runId']);

        $response = $this->getJson(route('youtube.parser.download-json', ['runId' => $run['runId']]))->assertOk();
        $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($before['data']['commentsIndex'], $payload['commentsIndex']);
        $this->assertSame(['status' => 'failed', 'complete' => false], $payload['collection']);
        $this->assertSame($before, $store->get($run['userId'], $run['runId']));
        Http::assertNothingSent();
    }

    public static function budgetReasons(): array
    {
        return array_map(static fn (string $reason): array => [$reason], ['duration', 'step_attempts', 'checkpoint_bytes', 'records', 'source_requests']);
    }

    #[DataProvider('unavailableSnapshots')]
    public function test_fallback_preserves_running_and_ordinary_missing_result_errors(array $overrides, int $status, string $code): void
    {
        [, $run] = $this->savedYouTubeRun($overrides);

        $this->getJson(route('youtube.parser.download-json', ['runId' => $run['runId']]))
            ->assertStatus($status)->assertJsonPath('code', $code);
        $this->getJson(route('youtube.parser.history'))->assertOk()->assertJsonPath('items.0.downloadable', false);
        Http::assertNothingSent();
    }

    public static function unavailableSnapshots(): array
    {
        return [
            'running' => [['status' => 'running'], 409, 'parser_run_not_downloadable'],
            'completed missing snapshot' => [['status' => 'completed'], 404, 'parser_run_result_not_found'],
            'ordinary failure' => [['resources' => ['exhausted' => 'unrelated_error']], 404, 'parser_run_result_not_found'],
            'missing collected data' => [['data' => null], 404, 'parser_run_result_not_found'],
        ];
    }

    #[DataProvider('downloadFormats')]
    public function test_reconstructed_partial_result_still_obeys_export_byte_budget(string $format): void
    {
        [, $run] = $this->savedYouTubeRun();
        config()->set('osint.parser_runs.limits.max_export_bytes', 12);
        app()->forgetInstance(ParserRunConfig::class);

        $this->getJson(route("youtube.parser.download-{$format}", ['runId' => $run['runId']]))
            ->assertServiceUnavailable()->assertJsonPath('code', 'parser_export_limit_exceeded')
            ->assertHeaderMissing('Content-Disposition');
    }

    public static function downloadFormats(): array
    {
        return [['json'], ['excel']];
    }

    private function savedYouTubeRun(array $overrides = []): array
    {
        Storage::fake('private');
        Http::preventStrayRequests();
        $this->freezeTime();
        $user = $this->paidUser();
        $store = app(YouTubeParserRunStore::class);
        $run = $store->create($user->id, ['videoId' => 'test-video']);
        $run = $store->mutate($user->id, $run['runId'], static fn (array $state): array => [
            ...$state,
            'status' => 'failed',
            'stage' => 'failed',
            'resources' => ['exhausted' => 'checkpoint_bytes'],
            'result' => null,
            'data' => ['commentsIndex' => [['commentId' => 'saved-comment', 'text' => 'saved text', 'publishedAt' => '2026-01-02T03:04:05Z']]],
            ...$overrides,
        ]);
        $this->actingAs($user);

        return [$store, $run];
    }
}
