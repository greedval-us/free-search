<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\PublicResourceNotFoundException;
use App\Models\MastodonAnalyticsReport;
use App\Models\User;
use App\Modules\Mastodon\Analytics\Reports\AnalyticsReportGenerator;
use App\Modules\Mastodon\Analytics\Reports\AnalyticsReportScheduleService;
use App\Modules\Mastodon\Analytics\Reports\Jobs\GenerateAnalyticsReport;
use App\Modules\Mastodon\Analytics\Reports\ScheduledMastodonAnalytics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScheduledMastodonAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.mastodon', ['token' => 'test-token', 'base_url' => 'https://mastodon.social',
            'timeout_seconds' => 2, 'retry_attempts' => 0]);
        config()->set('mastodon_analytics_reports.queue.connection', 'database');
        config()->set('access.plans.free', [...config('access.plans.free'), 'mastodon.analytics' => 100]);
        Http::preventStrayRequests();
    }

    public function test_collection_traverses_all_pages_and_includes_only_original_public_posts_in_exact_range(): void
    {
        Http::fake([
            'mastodon.social/api/v1/accounts/lookup*' => Http::response($this->profile()),
            'mastodon.social/api/v1/accounts/42/statuses*' => Http::sequence()
                ->push([
                    $this->statusFixture('200', '2026-10-10T00:00:00Z'),
                    $this->statusFixture('199', '2026-10-09T23:59:59.999999Z'),
                    $this->statusFixture('198', '2026-10-09T10:00:00Z', ['reblog' => ['id' => '100']]),
                    $this->statusFixture('197', '2026-10-09T10:00:00Z', ['visibility' => 'private']),
                ], 200, ['Link' => '<https://attacker.invalid/api/private?max_id=196>; rel="next"'])
                ->push([
                    $this->statusFixture('199', '2026-10-09T23:59:59.999999Z'),
                    $this->statusFixture('196', '2026-10-09T00:00:00Z', ['visibility' => 'unlisted', 'in_reply_to_id' => '100']),
                    $this->statusFixture('195', '2026-10-08T23:59:59.999999Z'),
                ]),
        ]);

        $report = $this->build();

        $this->assertSame(2, $report['summary']['postsCount']);
        $this->assertSame(0, $report['summary']['boostPostsCount']);
        $this->assertSame(1, $report['summary']['replyPostsCount']);
        $this->assertSame(10, $report['summary']['totalFavourites']);
        $this->assertSame('2026-10-09', $report['timeline'][0]['day']);
        $this->assertTrue($report['methodology']['complete']);
        $this->assertSame('statuses_available_on_configured_instance', $report['methodology']['collectionScope']);
        Http::assertSentCount(3);
        Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://mastodon.social/api/v1/accounts/42/statuses')
            && ($request->data()['max_id'] ?? null) === '196');
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'attacker.invalid'));
    }

    public function test_a_later_page_can_contain_a_newer_post_after_an_older_imported_post(): void
    {
        Http::fake([
            'mastodon.social/api/v1/accounts/lookup*' => Http::response($this->profile()),
            'mastodon.social/api/v1/accounts/42/statuses*' => Http::sequence()
                ->push([$this->statusFixture('100', '2020-01-01T00:00:00Z')], 200,
                    ['Link' => '<https://mastodon.social/api/v1/accounts/42/statuses?max_id=99>; rel="next"'])
                ->push([$this->statusFixture('99', '2026-10-09T10:00:00Z')]),
        ]);

        $this->assertSame(1, $this->build()['summary']['postsCount']);
    }

    public function test_days_are_grouped_in_the_selected_timezone(): void
    {
        $this->fakeStatuses([$this->statusFixture('100', '2026-10-09T22:30:00Z')]);

        $report = $this->build('Europe/Moscow');

        $this->assertSame('2026-10-10', $report['timeline'][0]['day']);
        $this->assertSame('Europe/Moscow', $report['range']['timezone']);
    }

    public function test_valid_empty_collection_produces_a_zero_report(): void
    {
        $this->fakeStatuses([]);

        $report = $this->build();

        $this->assertSame(0, $report['summary']['postsCount']);
        $this->assertSame([], $report['topPosts']);
        $this->assertTrue($report['methodology']['complete']);
    }

    public function test_page_limit_fails_instead_of_saving_partial_report(): void
    {
        config()->set('mastodon_analytics_reports.fetch_max_pages', 1);
        $this->fakeStatuses([$this->statusFixture('100', '2026-10-09T10:00:00Z')],
            ['Link' => '<https://mastodon.social/api/v1/accounts/42/statuses?max_id=99>; rel="next"']);

        try {
            $this->build();
            $this->fail('A partial report must not be returned.');
        } catch (ExternalServiceRequestException $exception) {
            $this->assertSame('mastodon_analytics_collection_limit', $exception->errorCode());
        }
    }

    public function test_repeated_cursor_fails_instead_of_looping(): void
    {
        Http::fake([
            'mastodon.social/api/v1/accounts/lookup*' => Http::response($this->profile()),
            'mastodon.social/api/v1/accounts/42/statuses*' => Http::sequence()
                ->push([$this->statusFixture('100', '2026-10-09T10:00:00Z')], 200,
                    ['Link' => '<https://mastodon.social/api/v1/accounts/42/statuses?max_id=99>; rel="next"'])
                ->push([$this->statusFixture('99', '2026-10-09T09:00:00Z')], 200,
                    ['Link' => '<https://mastodon.social/api/v1/accounts/42/statuses?max_id=99>; rel="next"']),
        ]);

        try {
            $this->build();
            $this->fail('A repeated cursor must not be followed.');
        } catch (ExternalServiceRequestException $exception) {
            $this->assertSame('mastodon_analytics_pagination_stalled', $exception->errorCode());
        }
        Http::assertSentCount(3);
    }

    public static function malformedStatuses(): array
    {
        return [
            'missing metric' => [['favourites_count' => null]],
            'negative metric' => [['reblogs_count' => -1]],
            'foreign account' => [['account' => ['id' => 'other']]],
            'missing visibility' => [['visibility' => null]],
            'invalid timestamp' => [['created_at' => 'not a date']],
            'invalid calendar day' => [['created_at' => '2026-09-31T10:00:00Z']],
            'invalid ID' => [['id' => '../other']],
            'invalid nested tags' => [['tags' => ['a tag']]],
        ];
    }

    #[DataProvider('malformedStatuses')]
    public function test_malformed_post_is_rejected_instead_of_becoming_zero_or_disappearing(array $overrides): void
    {
        $this->fakeStatuses([$this->statusFixture('100', '2026-10-09T10:00:00Z', $overrides)]);
        $this->expectException(ExternalServiceRequestException::class);
        $this->expectExceptionMessage('errors.api.mastodon.request_failed');

        $this->build();
    }

    public static function malformedCollections(): array
    {
        return ['null' => ['null'], 'object' => ['{}'], 'invalid JSON' => ['not json'], 'error response' => ['{"error":"failed"}']];
    }

    #[DataProvider('malformedCollections')]
    public function test_malformed_api_collection_is_rejected_instead_of_becoming_empty(string $body): void
    {
        Http::fake([
            'mastodon.social/api/v1/accounts/lookup*' => Http::response($this->profile()),
            'mastodon.social/api/v1/accounts/42/statuses*' => Http::response($body, 200, ['Content-Type' => 'application/json']),
        ]);
        $this->expectException(ExternalServiceRequestException::class);

        $this->build();
    }

    public function test_remote_account_is_resolved_via_configured_server_and_exact_search_match(): void
    {
        Http::fake([
            'mastodon.social/api/v1/accounts/lookup*' => Http::response(['error' => 'unknown account'], 404),
            'mastodon.social/api/v2/search*' => Http::response(['accounts' => [
                $this->profile(['id' => '41', 'acct' => 'someone@other.social']),
                $this->profile(['acct' => 'example@other.social', 'url' => 'https://other.social/@example']),
            ]]),
            'mastodon.social/api/v1/accounts/42/statuses*' => Http::response([]),
        ]);

        $report = app(ScheduledMastodonAnalytics::class)->build('https://other.social/@example',
            CarbonImmutable::parse('2026-10-09T00:00:00Z'), CarbonImmutable::parse('2026-10-09T23:59:59.999999Z'), 'UTC');

        $this->assertSame('example@other.social', $report['profile']['acct']);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/api/v2/search')
            && $request['q'] === 'example@other.social' && $request['resolve'] === 'true');
        Http::assertNotSent(fn ($request): bool => str_starts_with($request->url(), 'https://other.social/'));
    }

    public static function malformedNextLinks(): array
    {
        return [
            'missing cursor' => ['<https://mastodon.social/api/v1/accounts/42/statuses>; rel="next"'],
            'array cursor' => ['<https://mastodon.social/api/v1/accounts/42/statuses?max_id[]=100>; rel="next"'],
            'non-numeric cursor' => ['<https://mastodon.social/api/v1/accounts/42/statuses?max_id=abc>; rel="next"'],
            'missing URL' => ['broken; rel="next"'],
        ];
    }

    #[DataProvider('malformedNextLinks')]
    public function test_invalid_next_link_does_not_make_a_partial_report_look_complete(string $link): void
    {
        $this->fakeStatuses([$this->statusFixture('100', '2026-10-09T10:00:00Z')], ['Link' => $link]);
        $this->expectException(ExternalServiceRequestException::class);

        $this->build();
    }

    public function test_api_redirect_is_not_followed_and_cannot_be_saved_as_an_empty_report(): void
    {
        $requestRedirectSettings = [];
        Http::fake(function ($request, $options) use (&$requestRedirectSettings) {
            $requestRedirectSettings[] = $options['allow_redirects'] ?? null;

            return str_contains($request->url(), '/accounts/lookup')
                ? Http::response($this->profile())
                : Http::response('', 302, ['Location' => 'https://attacker.invalid/']);
        });
        try {
            $this->build();
            $this->fail('A redirect must not return an empty report.');
        } catch (ExternalServiceRequestException) {
            $this->assertSame([false, false], $requestRedirectSettings);
            Http::assertSentCount(2);
        }
    }

    public function test_unrelated_search_result_is_not_used_as_an_account(): void
    {
        Http::fake([
            'mastodon.social/api/v1/accounts/lookup*' => Http::response(['error' => 'unknown account'], 404),
            'mastodon.social/api/v2/search*' => Http::response(['accounts' => [
                $this->profile(['acct' => 'unrelated@mastodon.social', 'url' => 'https://mastodon.social/@unrelated']),
            ]]),
        ]);
        $this->expectException(PublicResourceNotFoundException::class);

        $this->build();
    }

    public function test_generator_restores_fractional_last_second_after_database_round_trip(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10T12:00:00Z'));
        $user = User::factory()->create();
        $this->fakeStatuses([
            $this->statusFixture('100', '2026-10-09T23:59:59.999999Z'),
            $this->statusFixture('101', '2026-10-10T00:00:00Z'),
        ]);
        Bus::fake([GenerateAnalyticsReport::class]);
        $schedule = app(AnalyticsReportScheduleService::class)->create($user,
            ['name' => 'Daily account', 'accounts' => ['example@mastodon.social'],
                'interval' => '1', 'send_time' => '09:00', 'timezone' => 'UTC']);
        app(AnalyticsReportScheduleService::class)->runNow($user, $schedule->id);
        $report = MastodonAnalyticsReport::query()->firstOrFail();

        app(AnalyticsReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(MastodonAnalyticsReport::COMPLETED, $report->fresh()->status);
        $this->assertSame(1, $report->fresh()->data['summary']['postsCount']);
        $this->assertSame('100', $report->fresh()->data['topPosts'][0]['id']);
    }

    private function build(string $timezone = 'UTC'): array
    {
        return app(ScheduledMastodonAnalytics::class)->build('example@mastodon.social',
            CarbonImmutable::parse('2026-10-09T00:00:00Z'), CarbonImmutable::parse('2026-10-09T23:59:59.999999Z'), $timezone);
    }

    private function fakeStatuses(array $items, array $headers = []): void
    {
        Http::fake([
            'mastodon.social/api/v1/accounts/lookup*' => Http::response($this->profile()),
            'mastodon.social/api/v1/accounts/42/statuses*' => Http::response($items, 200, $headers),
        ]);
    }

    private function profile(array $overrides = []): array
    {
        return [...['id' => '42', 'acct' => 'example@mastodon.social', 'username' => 'example',
            'url' => 'https://mastodon.social/@example', 'followers_count' => 100, 'following_count' => 10,
            'statuses_count' => 200, 'fields' => []], ...$overrides];
    }

    private function statusFixture(string $id, string $date, array $overrides = []): array
    {
        return [...['id' => $id, 'created_at' => $date, 'content' => '<p>Test post</p>', 'account' => $this->profile(),
            'visibility' => 'public', 'reblog' => null, 'favourites_count' => 5, 'reblogs_count' => 3, 'replies_count' => 2,
            'media_attachments' => [], 'mentions' => [], 'tags' => []], ...$overrides];
    }
}
