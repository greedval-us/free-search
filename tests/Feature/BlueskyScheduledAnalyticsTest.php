<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\PublicResourceNotFoundException;
use App\Modules\Bluesky\Analytics\Reports\Contracts\ScheduledBlueskyGatewayInterface;
use App\Modules\Bluesky\Analytics\Reports\ScheduledBlueskyAnalytics;
use App\Support\PublicBlueskyAccount;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BlueskyScheduledAnalyticsTest extends TestCase
{
    private const DID = 'did:plc:abcdefghijklmnopqrstuvwx';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_collects_complete_history_deduplicates_own_posts_and_uses_local_days_including_final_fractional_second(): void
    {
        $gateway = $this->mock(ScheduledBlueskyGatewayInterface::class);
        $gateway->shouldReceive('getProfiles')->once()->with(['example.bsky.social'])->andReturn($this->profiles());
        $gateway->shouldReceive('getAuthorFeed')->once()->with(self::DID, 100, null)->andReturn([
            'feed' => [
                ['post' => $this->feedPost('old', '2026-10-01T00:00:00Z')],
                ['post' => $this->feedPost('first', '2026-10-08T21:00:00Z')],
                ['post' => $this->feedPost('repost', '2026-10-09T10:00:00Z', 'did:plc:zyxwvutsrqponmlkjihgfedc')],
            ], 'cursor' => 'next',
        ]);
        $gateway->shouldReceive('getAuthorFeed')->once()->with(self::DID, 100, 'next')->andReturn([
            'feed' => [
                ['post' => $this->feedPost('first', '2026-10-08T21:00:00Z')],
                ['post' => $this->feedPost('last', '2026-10-09T20:59:59.999999Z')],
                ['post' => $this->feedPost('future', '2026-10-09T21:00:00Z')],
                ['post' => $this->feedPost('before', '2026-10-08T20:59:59.999999Z')],
            ],
        ]);

        $result = $this->build();

        $this->assertSame(2, $result['summary']['postsCount']);
        $this->assertSame(10, $result['summary']['totalLikes']);
        $this->assertSame(2, $result['meta']['pagesLoaded']);
        $this->assertSame(['2026-10-09'], array_column($result['timeline'], 'day'));
        $this->assertSame(2, $result['timeline'][0]['posts']);
        $this->assertSame(['first', 'last'], array_map(static fn (array $post): string => basename($post['uri']), $result['topPosts']));
        $this->assertTrue($result['methodology']['complete']);
        $this->assertSame('2026-10-09T23:59:59.999999+03:00', $result['range']['dateTo']);
        $this->assertSame('Europe/Moscow', $result['range']['timezone']);
        $this->assertSame(1, $result['range']['periodDays']);
    }

    public function test_empty_intermediate_feed_with_cursor_is_not_mistaken_for_the_end_of_history(): void
    {
        $gateway = $this->mock(ScheduledBlueskyGatewayInterface::class);
        $gateway->shouldReceive('getProfiles')->andReturn($this->profiles());
        $gateway->shouldReceive('getAuthorFeed')->with(self::DID, 100, null)->once()->andReturn(['feed' => [], 'cursor' => 'next']);
        $gateway->shouldReceive('getAuthorFeed')->with(self::DID, 100, 'next')->once()
            ->andReturn(['feed' => [['post' => $this->feedPost('late', '2026-10-09T10:00:00Z')]]]);

        $this->assertSame(1, $this->build()['summary']['postsCount']);
    }

    public function test_optional_reaction_counts_are_unknown_instead_of_zero_and_incomplete_statistics_are_not_ranked(): void
    {
        $gateway = $this->mock(ScheduledBlueskyGatewayInterface::class);
        $gateway->shouldReceive('getProfiles')->andReturn($this->profiles());
        $post = $this->feedPost('unknown', '2026-10-09T10:00:00Z');
        unset($post['likeCount'], $post['quoteCount']);
        $gateway->shouldReceive('getAuthorFeed')->once()->andReturn(['feed' => [['post' => $post]]]);

        $result = $this->build();

        $this->assertSame(1, $result['summary']['postsCount']);
        $this->assertNull($result['summary']['totalLikes']);
        $this->assertNull($result['summary']['totalQuotes']);
        $this->assertSame(2, $result['summary']['totalReposts']);
        $this->assertNull($result['timeline'][0]['likes']);
        $this->assertFalse($result['methodology']['statisticsComplete']);
        $this->assertSame(1, $result['methodology']['missingMetricCounts']['likeCount']);
        $this->assertSame([], $result['topPosts']);
    }

    public function test_absent_profile_fails_without_searching_for_an_unrelated_account(): void
    {
        $gateway = $this->mock(ScheduledBlueskyGatewayInterface::class);
        $gateway->shouldReceive('getProfiles')->once()->andReturn(['profiles' => []]);
        $gateway->shouldNotReceive('getAuthorFeed');

        $this->expectException(PublicResourceNotFoundException::class);
        $this->build();
    }

    public static function invalidPayloads(): array
    {
        return [
            'missing feed' => [[]],
            'API error envelope' => [['error' => 'temporarily unavailable']],
            'feed is not a list' => [['feed' => ['key' => []]]],
            'post is missing' => [['feed' => [[]]]],
            'invalid cursor' => [['feed' => [], 'cursor' => []]],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_feed_payloads_cannot_be_saved_as_successful_empty_reports(array $payload): void
    {
        $gateway = $this->mock(ScheduledBlueskyGatewayInterface::class);
        $gateway->shouldReceive('getProfiles')->andReturn($this->profiles());
        $gateway->shouldReceive('getAuthorFeed')->once()->andReturn($payload);

        $this->expectException(ExternalServiceRequestException::class);
        $this->build();
    }

    public function test_invalid_publication_timestamp_fails_instead_of_hiding_a_post(): void
    {
        $gateway = $this->mock(ScheduledBlueskyGatewayInterface::class);
        $gateway->shouldReceive('getProfiles')->andReturn($this->profiles());
        $gateway->shouldReceive('getAuthorFeed')->once()->andReturn(['feed' => [['post' => $this->feedPost('bad', '2026-09-31T00:00:00Z')]]]);

        $this->expectException(ExternalServiceRequestException::class);
        $this->build();
    }

    public function test_profile_mismatch_is_rejected_instead_of_silently_reporting_a_different_account(): void
    {
        $gateway = $this->mock(ScheduledBlueskyGatewayInterface::class);
        $profile = $this->profiles();
        $profile['profiles'][0]['handle'] = 'another.bsky.social';
        $gateway->shouldReceive('getProfiles')->once()->andReturn($profile);
        $gateway->shouldNotReceive('getAuthorFeed');

        $this->expectException(ExternalServiceRequestException::class);
        $this->build();
    }

    public function test_invalid_reaction_statistics_cannot_produce_a_misleading_success(): void
    {
        $gateway = $this->mock(ScheduledBlueskyGatewayInterface::class);
        $gateway->shouldReceive('getProfiles')->andReturn($this->profiles());
        $post = $this->feedPost('invalid_metric', '2026-10-09T10:00:00Z');
        $post['likeCount'] = -1;
        $gateway->shouldReceive('getAuthorFeed')->once()->andReturn(['feed' => [['post' => $post]]]);

        $this->expectException(ExternalServiceRequestException::class);
        $this->build();
    }

    public function test_pagination_cycle_is_terminal_and_collection_cap_cannot_produce_partial_success(): void
    {
        $gateway = $this->mock(ScheduledBlueskyGatewayInterface::class);
        $gateway->shouldReceive('getProfiles')->andReturn($this->profiles());
        $gateway->shouldReceive('getAuthorFeed')->twice()->andReturn(['feed' => [], 'cursor' => 'repeated']);
        try {
            $this->build();
            $this->fail('A cursor cycle cannot produce a report.');
        } catch (ExternalServiceRequestException $exception) {
            $this->assertSame('bluesky_analytics_pagination_stalled', $exception->errorCode());
        }
        config()->set('bluesky_analytics_reports.fetch_max_pages', 1);
        $gateway->shouldReceive('getAuthorFeed')->once()->andReturn(['feed' => [], 'cursor' => 'more']);
        try {
            $this->build();
            $this->fail('A collection cap cannot produce a report.');
        } catch (ExternalServiceRequestException $exception) {
            $this->assertSame('bluesky_analytics_collection_limit', $exception->errorCode());
        }
    }

    public function test_scheduled_reports_use_fixed_public_api_without_bluesky_credentials_or_redirects(): void
    {
        config()->set('services.bluesky.identifier', '');
        config()->set('services.bluesky.app_password', '');
        Http::fake([
            'https://public.api.bsky.app/xrpc/app.bsky.actor.getProfiles*' => Http::response($this->profiles()),
            'https://public.api.bsky.app/xrpc/app.bsky.feed.getAuthorFeed*' => Http::response(['feed' => []]),
        ]);

        $this->assertSame(0, $this->build()['summary']['postsCount']);
        Http::assertSentCount(2);
        Http::assertSent(static fn (Request $request): bool => str_starts_with($request->url(), 'https://public.api.bsky.app/')
            && ! $request->hasHeader('Authorization'));
    }

    public function test_redirect_and_invalid_json_are_report_errors(): void
    {
        foreach ([Http::response('', 302, ['Location' => 'http://127.0.0.1/private']), Http::response('invalid json')] as $response) {
            Http::fake(['*' => $response]);
            try {
                $this->build();
                $this->fail('Non-public responses must fail.');
            } catch (ExternalServiceRequestException) {
                Http::assertNotSent(static fn (Request $request): bool => str_contains($request->url(), '127.0.0.1'));
            }
        }
    }

    public static function accounts(): array
    {
        return [
            'handle' => ['Example.BSKY.social', 'example.bsky.social'],
            'at handle' => ['@Example.bsky.social', 'example.bsky.social'],
            'profile URL' => ['https://bsky.app/profile/Example.bsky.social/', 'example.bsky.social'],
            'profile without scheme' => ['bsky.app/profile/Example.bsky.social', 'example.bsky.social'],
            'PLC' => [self::DID, self::DID],
            'web DID' => ['did:web:User.Example.com', 'did:web:User.Example.com'],
            'PLC URL' => ['https://bsky.app/profile/'.self::DID, self::DID],
            'invalid PLC' => ['did:plc:too_short', null],
            'web DID port' => ['did:web:example.com%3A8080', null],
            'web DID path' => ['did:web:example.com:users:alice', null],
            'other DID method' => ['did:key:abc', null],
            'bare username' => ['example', null],
            'private name' => ['actor.localhost', null],
            'invalid handle' => ['handle.invalid', null],
            'IP' => ['127.0.0.1', null],
            'label too long' => [str_repeat('a', 64).'.com', null],
            'post URL' => ['https://bsky.app/profile/example.bsky.social/post/123', null],
            'wrong host' => ['https://bsky.app.attacker.com/profile/example.bsky.social', null],
            'credentials' => ['https://user:secret@bsky.app/profile/example.bsky.social', null],
            'port' => ['https://bsky.app:8443/profile/example.bsky.social', null],
            'query' => ['https://bsky.app/profile/example.bsky.social?query=1', null],
            'fragment' => ['https://bsky.app/profile/example.bsky.social#posts', null],
            'HTTP' => ['http://bsky.app/profile/example.bsky.social', null],
            'newline' => ["actor\n.bsky.social", null],
            'array' => [[], null],
            'number' => [123, null],
        ];
    }

    #[DataProvider('accounts')]
    public function test_account_normalization_accepts_public_actor_identifiers_only(mixed $input, ?string $expected): void
    {
        $this->assertSame($expected, PublicBlueskyAccount::normalize($input));
    }

    private function build(): array
    {
        return app(ScheduledBlueskyAnalytics::class)->build('example.bsky.social',
            CarbonImmutable::parse('2026-10-08T21:00:00Z'), CarbonImmutable::parse('2026-10-09T20:59:59Z'), 'Europe/Moscow');
    }

    private function profiles(): array
    {
        return ['profiles' => [['did' => self::DID, 'handle' => 'example.bsky.social', 'displayName' => 'Example']]];
    }

    private function feedPost(string $id, string $createdAt, string $did = self::DID): array
    {
        return ['uri' => 'at://'.$did.'/app.bsky.feed.post/'.$id,
            'author' => ['did' => $did, 'handle' => 'example.bsky.social'],
            'record' => ['text' => 'Public text', 'createdAt' => $createdAt],
            'replyCount' => 1, 'repostCount' => 2, 'likeCount' => 5, 'quoteCount' => 0];
    }
}
