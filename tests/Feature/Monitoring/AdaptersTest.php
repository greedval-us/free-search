<?php

namespace Tests\Feature\Monitoring;

use App\Models\MonitoringSource;
use App\Modules\Bluesky\BlueskyApiClient;
use App\Modules\Bluesky\Monitoring\BlueskySourceAdapter;
use App\Modules\Bluesky\Presenters\BlueskyActorPresenter;
use App\Modules\Bluesky\Support\BlueskyActorResolver;
use App\Modules\Bluesky\Support\BlueskyApiConfig;
use App\Modules\Mastodon\Actions\Request\LoadAccountStatusesAction;
use App\Modules\Mastodon\MastodonApiClient;
use App\Modules\Mastodon\Monitoring\MastodonSourceAdapter;
use App\Modules\Mastodon\Presenters\MastodonAccountPresenter;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;
use App\Modules\Mastodon\Support\MastodonApiConfig;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionDeduplicator;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionFingerprintFactory;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\SearxngNewsFeedFetcher;
use App\Modules\NewsMediaIntel\Monitoring\NewsSourceAdapter;
use App\Modules\Telegram\Monitoring\TelegramSourceAdapter;
use App\Modules\Telegram\Tracking\TrackingConfig;
use App\Modules\Telegram\Tracking\TrackingException;
use App\Modules\Telegram\Tracking\TrackingMatcher;
use App\Modules\Telegram\Tracking\TrackingPageProcessor;
use App\Modules\YouTube\Monitoring\YouTubeSourceAdapter;
use App\Modules\YouTube\Support\YouTubeApiConfig;
use App\Modules\YouTube\Support\YouTubeChannelInputNormalizer;
use App\Modules\YouTube\Support\YouTubeChannelResolver;
use App\Modules\YouTube\YouTubeDataApiClient;
use App\Services\Monitoring\SourceUnavailable;
use App\Support\MadelineProto\MadelineProtoOperationGuard;
use App\Support\MadelineProto\SessionOperationException;
use App\Support\Observability\ExternalServiceLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeTrackingGateway;
use Tests\TestCase;

class AdaptersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['monitoring.request_gap_seconds' => 0, 'monitoring.page_size' => 2]);
        $this->travelTo(CarbonImmutable::parse('2026-10-06T12:00:00Z'));
    }

    public function test_telegram_overview_reads_history_with_explicit_all_mode_and_resumable_cursor(): void
    {
        $gateway = new FakeTrackingGateway;
        config(['telegram_tracking.page_size' => 2]);
        $adapter = new TelegramSourceAdapter($gateway, new TrackingPageProcessor(app(TrackingConfig::class), new TrackingMatcher));
        $source = $this->source('telegram', $adapter->resolve('https://t.me/publicgroup'));
        $gateway->pages = [[$this->telegramMessage(8, 'No keyword required'), $this->telegramMessage(7, '=formula')],
            [['_' => 'messageService', 'id' => 6, 'date' => $this->windowFrom()->timestamp + 1]]];

        $page = $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), null);
        $next = $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), $page->cursor);

        $this->assertSame('all', $source->configuration['mode']);
        $this->assertSame('history', $gateway->requests[0]['method']);
        $this->assertNull($gateway->resolutions[0]['keyword']);
        $this->assertSame('7', $page->cursor);
        $this->assertFalse($page->complete);
        $this->assertSame(['8', '7'], array_column($page->items, 'external_id'));
        $this->assertSame('https://t.me/publicgroup/8', $page->items[0]['url']);
        $this->assertSame('No keyword required', $page->items[0]['text']);
        $this->assertSame('=formula', $page->items[1]['text']);
        $this->assertSame([], $next->items);
        $this->assertTrue($next->complete);
        $this->assertTrue($gateway->requests[0]['window_start']->equalTo($gateway->requests[1]['window_start']));
    }

    public function test_telegram_flood_wait_is_not_shortened_and_does_not_hide_failed_collection(): void
    {
        $gateway = new FakeTrackingGateway;
        $adapter = new TelegramSourceAdapter($gateway, app(TrackingPageProcessor::class));
        $source = $this->source('telegram', $adapter->resolve('@publicgroup'));
        $gateway->failure = new TrackingException('flood_wait', 100000);
        try {
            $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), '42');
            $this->fail('FLOOD_WAIT must defer collection.');
        } catch (SourceUnavailable $exception) {
            $this->assertSame('flood_wait', $exception->reason);
            $this->assertSame(100000, $exception->retryAfter);
            $this->assertSame(42, $gateway->requests[0]['offset']);
        }
    }

    public function test_telegram_collection_uses_half_open_fixed_time_window_and_preserves_sender_filter(): void
    {
        $gateway = new FakeTrackingGateway;
        config(['telegram_tracking.page_size' => 100]);
        $adapter = new TelegramSourceAdapter($gateway, new TrackingPageProcessor(app(TrackingConfig::class), new TrackingMatcher));
        $source = $this->source('telegram', $adapter->resolve('@publicgroup'));
        $source->configuration = [...$source->configuration, 'mode' => 'user', 'sender_id' => '42'];
        $gateway->pages = [[
            [...$this->telegramMessage(4, 'At cutoff'), 'date' => $this->windowUntil()->timestamp],
            [...$this->telegramMessage(3, 'Wrong sender'), 'from_id' => ['_' => 'peerUser', 'user_id' => 99]],
            [...$this->telegramMessage(2, 'At start'), 'date' => $this->windowFrom()->timestamp],
            [...$this->telegramMessage(1, 'Before start'), 'date' => $this->windowFrom()->timestamp - 1],
        ]];

        $page = $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), null);

        $this->assertSame(['2'], array_column($page->items, 'external_id'));
        $this->assertTrue($page->complete);
        $this->assertSame('42', $page->items[0]['author']);
    }

    public function test_youtube_collects_channel_uploads_and_video_metrics_without_running_comment_parser(): void
    {
        Http::preventStrayRequests();
        $channel = 'UCabcdefghijklmnopqrstuv';
        Http::fake([
            'www.googleapis.com/youtube/v3/channels*' => Http::response(['items' => [[
                'id' => $channel, 'snippet' => ['title' => 'Channel'], 'contentDetails' => ['relatedPlaylists' => ['uploads' => 'UUuploads']],
            ]]]),
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response(['items' => [[
                'contentDetails' => ['videoId' => 'video123', 'videoPublishedAt' => '2026-10-05T10:00:00Z'],
            ]], 'nextPageToken' => 'second']),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => [[
                'id' => 'video123', 'snippet' => ['channelId' => $channel, 'channelTitle' => 'Channel', 'title' => 'New video',
                    'description' => 'Description', 'publishedAt' => '2026-10-05T10:00:00Z'],
                'status' => ['privacyStatus' => 'public'], 'statistics' => ['viewCount' => '12', 'likeCount' => '2'],
            ]]]),
        ]);
        $adapter = $this->youtube();
        $source = $this->source('youtube', $adapter->resolve($channel));

        $page = $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), null);

        $this->assertSame('UUuploads', $source->configuration['uploads_playlist_id']);
        $this->assertSame('second', $page->cursor);
        $this->assertFalse($page->complete);
        $this->assertSame('video123', $page->items[0]['external_id']);
        $this->assertSame(12, $page->items[0]['metrics']['views']);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/playlistItems') && $request['playlistId'] === 'UUuploads');
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/comment') || str_contains($request->url(), '/search'));
        Http::assertSentCount(3);
    }

    public function test_youtube_rate_limit_preserves_server_retry_after_for_every_project_sharing_key(): void
    {
        Http::preventStrayRequests();
        Http::fake(['www.googleapis.com/youtube/v3/channels*' => Http::response([], 429, ['Retry-After' => '7200'])]);
        $adapter = $this->youtube();
        foreach (['youtube_rate_limited', 'cooldown'] as $reason) {
            try {
                $adapter->resolve('UCabcdefghijklmnopqrstuv');
                $this->fail('Rate limited integrations must defer.');
            } catch (SourceUnavailable $exception) {
                $this->assertSame($reason, $exception->reason);
                $this->assertSame(7200, $exception->retryAfter);
            }
        }
        Http::assertSentCount(1);
    }

    public function test_youtube_encoded_unicode_channel_handle_resolves_to_verified_channel_id(): void
    {
        Http::preventStrayRequests();
        Http::fake(['www.googleapis.com/youtube/v3/channels*' => Http::response(['items' => [[
            'id' => 'UCabcdefghijklmnopqrstuv', 'snippet' => ['title' => 'Канал'], 'contentDetails' => ['relatedPlaylists' => ['uploads' => 'UUuploads']],
        ]]])]);
        $resolved = $this->youtube()->resolve('https://www.youtube.com/@'.rawurlencode('канал'));

        $this->assertSame('UCabcdefghijklmnopqrstuv', $resolved['identity']);
        Http::assertSent(fn (Request $request) => ($request->data()['forHandle'] ?? null) === '@канал');
        Http::assertSentCount(2);
    }

    public function test_unconfigured_youtube_is_unavailable_without_external_request(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $gateway = new YouTubeDataApiClient(YouTubeApiConfig::fromArray(['key' => '']), new ExternalServiceLogger);
        $adapter = new YouTubeSourceAdapter($gateway, new YouTubeChannelResolver($gateway, new YouTubeChannelInputNormalizer));
        try {
            $adapter->resolve('UCabcdefghijklmnopqrstuv');
            $this->fail('An unconfigured gateway must not return demonstration data.');
        } catch (SourceUnavailable $exception) {
            $this->assertSame('youtube_not_configured', $exception->reason);
        }
        Http::assertNothingSent();
    }

    public function test_bluesky_resolves_exact_did_and_collects_authors_original_posts_with_one_page_cursor(): void
    {
        Http::preventStrayRequests();
        $did = 'did:plc:sampleactor';
        Http::fake([
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::response(['accessJwt' => 'test-token']),
            'bsky.social/xrpc/app.bsky.actor.getProfiles*' => Http::response(['profiles' => [['did' => $did, 'handle' => 'sample.bsky.social']]]),
            'bsky.social/xrpc/app.bsky.feed.getAuthorFeed*' => Http::response(['feed' => [
                ['post' => ['uri' => 'at://'.$did.'/app.bsky.feed.post/abc', 'author' => ['did' => $did, 'handle' => 'renamed.bsky.social'],
                    'record' => ['text' => 'New post', 'createdAt' => '2026-10-05T10:00:00Z'], 'likeCount' => 3]],
                ['reason' => ['by' => ['did' => $did]], 'post' => ['uri' => 'at://did:plc:another/app.bsky.feed.post/def']],
            ], 'cursor' => 'older']),
        ]);
        $adapter = $this->bluesky();
        $source = $this->source('bluesky', $adapter->resolve('@sample.bsky.social'));

        $page = $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), null);

        $this->assertSame($did, $source->identity);
        $this->assertCount(1, $page->items);
        $this->assertSame('at://'.$did.'/app.bsky.feed.post/abc', $page->items[0]['external_id']);
        $this->assertSame('renamed.bsky.social', $page->items[0]['author']);
        $this->assertSame('older', $page->cursor);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '.getAuthorFeed') && $request['actor'] === $did
            && (int) $request['limit'] === 2 && $request['filter'] === 'posts_with_replies');
        Http::assertSentCount(3);
    }

    public function test_unknown_bluesky_handle_does_not_fall_back_to_fuzzy_search_or_authenticated_account(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::response(['accessJwt' => 'test-token']),
            'bsky.social/xrpc/app.bsky.actor.getProfiles*' => Http::response(['profiles' => []]),
        ]);
        try {
            $this->bluesky()->resolve('missing.bsky.social');
            $this->fail('Missing exact account must fail validation.');
        } catch (SourceUnavailable $exception) {
            $this->assertSame('bluesky_account_unavailable', $exception->reason);
        }
        Http::assertSentCount(2);
    }

    public function test_bluesky_expired_token_is_renewed_once_in_a_long_lived_gateway(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::sequence()->push(['accessJwt' => 'old'])->push(['accessJwt' => 'new']),
            'bsky.social/xrpc/app.bsky.actor.getProfiles*' => Http::sequence()->push(['error' => 'ExpiredToken'], 401)
                ->push(['profiles' => [['did' => 'did:plc:sample', 'handle' => 'sample.bsky.social']]]),
        ]);

        $resolved = $this->bluesky()->resolve('sample.bsky.social');

        $this->assertSame('did:plc:sample', $resolved['identity']);
        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '.getProfiles') && $request->hasHeader('Authorization', 'Bearer new'));
    }

    public function test_mastodon_uses_operator_instance_and_keeps_instance_scoped_account_identity(): void
    {
        Http::preventStrayRequests();
        $account = ['id' => '42', 'acct' => 'author@remote.example', 'url' => 'https://remote.example/@author', 'display_name' => 'Author'];
        Http::fake([
            'mastodon.social/api/v1/accounts/lookup*' => Http::response($account),
            'mastodon.social/api/v1/accounts/42/statuses*' => Http::response([[
                'id' => '99', 'uri' => 'https://remote.example/users/author/statuses/99', 'url' => 'https://remote.example/@author/99',
                'content' => '<p>A &amp; B</p>', 'created_at' => '2026-10-05T10:00:00Z', 'account' => $account, 'visibility' => 'public',
            ]], 200, ['Link' => '<https://mastodon.social/api/v1/accounts/42/statuses?max_id=98>; rel="next"']),
        ]);
        $adapter = $this->mastodon();
        $source = $this->source('mastodon', $adapter->resolve('https://remote.example/@author'));

        $page = $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), null);

        $this->assertSame('https://mastodon.social', $source->configuration['instance']);
        $this->assertSame('42', $source->configuration['account_id']);
        $this->assertSame('A & B', $page->items[0]['text']);
        $this->assertSame('https://remote.example/users/author/statuses/99', $page->items[0]['external_id']);
        $this->assertSame('98', $page->cursor);
        Http::assertNotSent(fn (Request $request) => str_starts_with($request->url(), 'https://remote.example'));
        Http::assertSentCount(2);
        $this->expectException(SourceUnavailable::class);
        $source->configuration = [...$source->configuration, 'instance' => 'https://another.example'];
        $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), null);
    }

    public function test_news_uses_saved_query_filters_domains_retains_unknown_dates_and_engine_failures(): void
    {
        Http::preventStrayRequests();
        Http::fake(['127.0.0.1:8088/search' => Http::response(['results' => [
            ['title' => 'Known date', 'url' => 'https://news.example.com/1?utm_source=search', 'content' => 'Known', 'publishedDate' => '2026-10-05T10:00:00Z'],
            ['title' => 'Unknown date', 'url' => 'https://news.example.com/2', 'content' => 'Unknown', 'publishedDate' => 'yesterday'],
            ['title' => 'Wrong domain', 'url' => 'https://unrelated.example.org/3', 'publishedDate' => '2026-10-05T10:00:00Z'],
            ['title' => 'Internal', 'url' => 'http://127.0.0.1/admin'],
        ], 'unresponsive_engines' => [['bing news', 'timeout']]])]);
        $adapter = $this->news();
        $source = $this->source('news', $adapter->resolve('OpenAI site:news.example.com'));

        $page = $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), null);

        $this->assertSame(['news.example.com'], $source->configuration['domains']);
        $this->assertCount(2, $page->items);
        $this->assertNull($page->items[1]['published_at']);
        $this->assertContains('engine_unavailable:bing news', $page->warnings);
        $this->assertContains('publication_date_unavailable', $page->warnings);
        $this->assertFalse($page->complete);
        Http::assertSent(fn (Request $request) => $request['q'] === 'OpenAI site:news.example.com' && $request['categories'] === 'news');
        Http::assertSentCount(2);
        $repeat = $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), $page->cursor);
        $this->assertTrue($repeat->complete);
        $this->assertContains('news_pagination_repeated', $repeat->warnings);
    }

    public function test_news_reached_page_limit_is_explicit_and_never_claims_index_completeness(): void
    {
        Http::preventStrayRequests();
        Http::fake(['127.0.0.1:8088/search' => Http::response(['results' => [
            ['title' => 'Article', 'url' => 'https://news.example.com/1', 'publishedDate' => '2026-10-05T10:00:00Z'],
        ]])]);
        $adapter = $this->news(1);
        $source = $this->source('news', $adapter->resolve('OpenAI'));
        $page = $adapter->fetch($source, $this->windowFrom(), $this->windowUntil(), null);

        $this->assertTrue($page->complete);
        $this->assertContains('news_pagination_limit', $page->warnings);
        $this->assertContains('search_index_coverage_limited', $page->warnings);
        Http::assertSentCount(2);
    }

    public function test_user_supplied_news_domain_and_mastodon_urls_never_choose_internal_request_hosts(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        foreach ([fn () => $this->news()->resolve('OpenAI site:127.0.0.1'),
            fn () => $this->news()->resolve('OpenAI site:company.internal'),
            fn () => $this->mastodon()->resolve('http://127.0.0.1/@admin'),
            fn () => $this->mastodon()->resolve('https://localhost/@admin'),
            fn () => $this->mastodon()->resolve('https://author:secret@remote.example/@author'),
        ] as $resolve) {
            try {
                $resolve();
                $this->fail('Unsafe source must be rejected before making any HTTP request.');
            } catch (SourceUnavailable $exception) {
                $this->assertContains($exception->reason, ['invalid_source', 'invalid_news_domain']);
            }
        }
        Http::assertNothingSent();
    }

    public function test_shared_telegram_operation_guard_honors_manual_flood_wait_and_busy_session(): void
    {
        $guard = new MadelineProtoOperationGuard;
        try {
            $guard->run('shared', fn () => throw new \RuntimeException('FLOOD_WAIT_100000'));
            $this->fail('Flood wait must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('FLOOD_WAIT_100000', $exception->getMessage());
        }
        try {
            $guard->run('shared', fn () => $this->fail('The source must not contact a cooling session.'));
            $this->fail('A shared cooldown must defer.');
        } catch (SessionOperationException $exception) {
            $this->assertSame('cooldown', $exception->reason);
            $this->assertSame(100000, $exception->retryAfter);
        }
        $lock = Cache::lock('telegram-tracking:session:busy:lock', 60);
        $lock->get();
        try {
            $guard->run('busy', fn () => $this->fail('A claimed session must not be contacted.'));
            $this->fail('A shared session lock must defer.');
        } catch (SessionOperationException $exception) {
            $this->assertSame('busy', $exception->reason);
        } finally {
            $lock->release();
        }
    }

    private function source(string $platform, array $resolved): MonitoringSource
    {
        return new MonitoringSource(['platform' => $platform, ...$resolved]);
    }

    private function windowFrom(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-05T00:00:00Z');
    }

    private function windowUntil(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-06T00:00:00Z');
    }

    private function telegramMessage(int $id, string $text): array
    {
        return ['_' => 'message', 'id' => $id, 'date' => $this->windowFrom()->timestamp + 3600, 'message' => $text,
            'from_id' => ['_' => 'peerUser', 'user_id' => 42], 'views' => 3];
    }

    private function youtube(): YouTubeSourceAdapter
    {
        config(['services.youtube.key' => 'test-key']);
        $gateway = new YouTubeDataApiClient(YouTubeApiConfig::fromArray(['key' => 'test-key', 'retry_attempts' => 0]), new ExternalServiceLogger);

        return new YouTubeSourceAdapter($gateway, new YouTubeChannelResolver($gateway, new YouTubeChannelInputNormalizer));
    }

    private function bluesky(): BlueskySourceAdapter
    {
        $gateway = new BlueskyApiClient(BlueskyApiConfig::fromArray(['identifier' => 'test', 'app_password' => 'test-password', 'retry_attempts' => 0]), new ExternalServiceLogger);

        return new BlueskySourceAdapter($gateway, new BlueskyActorResolver($gateway, new BlueskyActorPresenter));
    }

    private function mastodon(): MastodonSourceAdapter
    {
        $configuration = MastodonApiConfig::fromArray(['token' => 'test-token', 'retry_attempts' => 0]);
        $gateway = new MastodonApiClient($configuration, new ExternalServiceLogger);
        $statuses = new LoadAccountStatusesAction($gateway, new MastodonStatusPresenter(new MastodonAccountPresenter));

        return new MastodonSourceAdapter($gateway, $configuration, $statuses);
    }

    private function news(int $maxPages = 3): NewsSourceAdapter
    {
        $configuration = NewsMediaIntelConfig::fromArray(['searxng' => ['base_url' => 'http://127.0.0.1:8088', 'max_pages' => $maxPages],
            'deduplication' => ['query_trackers' => ['utm_source']]]);
        $fingerprints = new NewsMentionFingerprintFactory($configuration);
        $fetcher = new SearxngNewsFeedFetcher($configuration, new ExternalServiceLogger, new NewsMentionDeduplicator($fingerprints));

        return new NewsSourceAdapter($fetcher, $configuration, $fingerprints);
    }
}
