<?php

namespace Tests\Feature;

use App\Models\ParserRun;
use App\Models\User;
use App\Modules\Bluesky\BlueskyApiClient;
use App\Modules\Bluesky\Support\BlueskyApiConfig;
use App\Modules\Mastodon\MastodonApiClient;
use App\Modules\Mastodon\Support\MastodonApiConfig;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\ParserSupport\ParserRunSourceRequestBudget;
use App\Modules\ParserSupport\ParserRunSourceRequestBudgetExceeded;
use App\Modules\Telegram\Actions\AbstractTelegramAction;
use App\Modules\YouTube\Parser\YouTubeParserRunStore;
use App\Modules\YouTube\Support\YouTubeApiConfig;
use App\Modules\YouTube\YouTubeDataApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ParserRunSourceRequestBudgetTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('httpSources')]
    public function test_every_http_retry_spends_the_source_budget_and_partial_data_remains_downloadable(string $source, string $url): void
    {
        Storage::fake('private');
        Http::preventStrayRequests();
        Http::fake([$url => Http::sequence()->push([], 503)->push([], 503)->push([], 503)->push([], 503)->push([], 503)]);
        $this->configureLimit(2);
        $user = User::factory()->create();
        $store = app(YouTubeParserRunStore::class);
        $run = $store->create($user->id, []);
        $store->mutate($user->id, $run['runId'], static function (array $state): array {
            $state['data']['comments'] = [['id' => 'saved-comment']];

            return $state;
        });

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static function (array $state) use ($source): array {
                match ($source) {
                    'youtube' => app(YouTubeDataApiClient::class)->commentThreads(['videoId' => 'test-video']),
                    'mastodon' => app(MastodonApiClient::class)->search(['q' => 'test']),
                    'bluesky' => app(BlueskyApiClient::class)->searchPosts(['q' => 'test']),
                };

                return $state;
            }, static fn (array $state): array => ['comments' => $state['data']['comments']],
        );

        Http::assertSentCount(2);
        $this->assertSame('source_requests', $after['resources']['exhausted']);
        $this->assertSame('failed', $after['status']);
        $this->assertSame([['id' => 'saved-comment']], $after['result']['comments']);
        $this->assertSame(2, ParserRun::query()->where('run_id', $run['runId'])->value('source_request_count'));
    }

    public static function httpSources(): array
    {
        return [
            'youtube' => ['youtube', 'https://www.googleapis.com/youtube/v3/*'],
            'mastodon' => ['mastodon', 'https://mastodon.test/*'],
            'bluesky session retry' => ['bluesky', 'https://bluesky.test/*'],
        ];
    }

    #[DataProvider('httpSources')]
    public function test_a_redirect_response_without_an_http_exception_keeps_existing_client_behavior(string $source, string $url): void
    {
        Http::preventStrayRequests();
        $payload = ['accessJwt' => 'synthetic-token', 'items' => []];
        Http::fake([$url => Http::response($payload, 302)]);
        $this->configureLimit(1);

        $result = match ($source) {
            'youtube' => app(YouTubeDataApiClient::class)->commentThreads(['videoId' => 'test-video']),
            'mastodon' => app(MastodonApiClient::class)->search(['q' => 'test']),
            'bluesky' => app(BlueskyApiClient::class)->searchPosts(['q' => 'test']),
        };

        $this->assertSame($payload, $result);
        Http::assertSentCount($source === 'bluesky' ? 2 : 1);
    }

    public function test_bluesky_authentication_spends_budget_before_the_data_request(): void
    {
        Storage::fake('private');
        Http::preventStrayRequests();
        Http::fake(['https://bluesky.test/*' => Http::response(['accessJwt' => 'synthetic-token'])]);
        $this->configureLimit(1);
        $user = User::factory()->create();
        $store = app(YouTubeParserRunStore::class);
        $run = $store->create($user->id, []);

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static function (array $state): array {
                app(BlueskyApiClient::class)->searchPosts(['q' => 'test']);

                return $state;
            },
        );

        Http::assertSentCount(1);
        Http::assertSent(static fn ($request): bool => str_ends_with($request->url(), '/com.atproto.server.createSession'));
        $this->assertSame('source_requests', $after['resources']['exhausted']);
    }

    public function test_a_new_service_instance_cannot_reset_the_source_request_counter(): void
    {
        Storage::fake('private');
        Http::preventStrayRequests();
        Http::fake(['https://www.googleapis.com/youtube/v3/*' => Http::response(['items' => []])]);
        $this->freezeTime();
        $this->configureLimit(1);
        $user = User::factory()->create();
        $store = app(YouTubeParserRunStore::class);
        $run = $store->create($user->id, []);
        $advance = static function (array $state): array {
            app(YouTubeDataApiClient::class)->commentThreads(['videoId' => 'test-video']);
            $state['progress']++;

            return $state;
        };
        app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'], $advance);
        app()->forgetInstance(ParserRunSourceRequestBudget::class);
        $this->travel(3)->seconds();

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'], $advance);

        Http::assertSentCount(1);
        $this->assertSame('source_requests', $after['resources']['exhausted']);
        $this->assertSame(1, ParserRun::query()->where('run_id', $run['runId'])->value('source_request_count'));
    }

    public function test_stopped_and_expired_runs_cannot_reserve_a_source_request(): void
    {
        Storage::fake('private');
        $this->configureLimit(2);
        $user = User::factory()->create();
        $store = app(YouTubeParserRunStore::class);
        $run = $store->create($user->id, []);
        $metadata = ParserRun::query()->where('run_id', $run['runId'])->firstOrFail();
        $budget = app(ParserRunSourceRequestBudget::class);

        foreach ([['status' => 'stopped'], ['status' => 'running', 'expires_at' => now()->subSecond()]] as $attributes) {
            $metadata->forceFill($attributes)->save();
            try {
                $budget->duringRun('youtube', $user->id, $run['runId'], fn () => $budget->charge());
                $this->fail('An unavailable run must not call a source.');
            } catch (ParserRunSourceRequestBudgetExceeded) {
                $this->assertSame(0, $metadata->fresh()->source_request_count);
            }
        }
        $budget->charge(); // Scope cleanup also applies after an exception.
        $this->assertArrayNotHasKey('source_request_count', $metadata->fresh()->toArray());
    }

    public function test_a_telegram_action_cannot_hide_source_budget_exhaustion_with_a_fallback(): void
    {
        Storage::fake('private');
        $this->configureLimit(1);
        $user = User::factory()->create();
        $store = app(YouTubeParserRunStore::class);
        $run = $store->create($user->id, []);
        $action = new class extends AbstractTelegramAction
        {
            public int $calls = 0;

            public function collect(): ?array
            {
                try {
                    return $this->executeWithRetry(function (): array {
                        $this->calls++;
                        throw new RuntimeException('Transient network timeout');
                    });
                } catch (\Throwable) {
                    return null;
                }
            }
        };
        Sleep::fake();

        $after = app(ParserRunExecutionCoordinator::class)->advance($store, $user->id, $run['runId'],
            static function (array $state) use ($action): array {
                $action->collect();
                $state['status'] = 'completed';

                return $state;
            }, static fn (): array => ['comments' => [['id' => 'previously-saved']]],
        );

        $this->assertSame(1, $action->calls);
        $this->assertSame('failed', $after['status']);
        $this->assertSame('source_requests', $after['resources']['exhausted']);
        $this->assertSame([['id' => 'previously-saved']], $after['result']['comments']);
        Sleep::assertSleptTimes(1);
    }

    private function configureLimit(int $limit): void
    {
        config()->set('osint.parser_runs.limits.max_source_requests', $limit);
        config()->set('services.youtube', ['key' => 'synthetic-test-key', 'retry_attempts' => 5, 'retry_delay_milliseconds' => 0]);
        config()->set('services.mastodon', ['token' => 'synthetic-token', 'base_url' => 'https://mastodon.test', 'retry_attempts' => 5, 'retry_delay_milliseconds' => 0]);
        config()->set('services.bluesky', ['identifier' => 'test.invalid', 'app_password' => 'synthetic-password', 'pds_url' => 'https://bluesky.test', 'retry_attempts' => 5, 'retry_delay_milliseconds' => 0]);
        app()->forgetInstance(ParserRunConfig::class);
        app()->forgetInstance(YouTubeApiConfig::class);
        app()->forgetInstance(MastodonApiConfig::class);
        app()->forgetInstance(BlueskyApiConfig::class);
    }
}
