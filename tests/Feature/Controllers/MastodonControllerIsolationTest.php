<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Modules\Mastodon\Analytics\Contracts\MastodonAnalyticsApplicationServiceInterface;
use App\Modules\Mastodon\DTO\Request\MastodonAnalyticsQueryDTO;
use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAnalyticsResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonSearchResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonStatusContextResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonTagTimelineResultDTO;
use App\Modules\Mastodon\Search\Contracts\MastodonSearchApplicationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\Feature\Controllers\Concerns\CreatesPaidUser;
use Tests\TestCase;

class MastodonControllerIsolationTest extends TestCase
{
    use CreatesPaidUser, RefreshDatabase;

    public function test_mastodon_search_controller_returns_service_payload(): void
    {
        $user = $this->paidUser();

        $this->mock(MastodonSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('search')
                ->once()
                ->with(Mockery::on(
                    fn (MastodonSearchQueryDTO $query): bool => $query->query === 'osint'
                        && $query->type === 'statuses'
                        && $query->limit === 5
                        && $query->resolve
                        && $query->language === 'en'
                        && $query->hasMedia === true
                        && $query->hasLinks === true
                        && $query->hasReplies === true
                        && $query->author === '@analyst'
                        && $query->dateFrom === '2026-06-01T10:00'
                        && $query->dateTo === '2026-06-02T18:00'
                        && $query->instanceDomain === 'mastodon.social'
                ))
                ->andReturn(new MastodonSearchResultDTO(
                    statuses: [[
                        'id' => 'status-1',
                        'content' => 'OSINT in fediverse',
                    ]],
                    accounts: [],
                    hashtags: [],
                    pagination: [
                        'query' => 'osint',
                        'type' => 'statuses',
                        'limit' => 5,
                        'offset' => 0,
                        'nextOffset' => null,
                        'hasMore' => false,
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('mastodon.search.index', [
                'q' => 'osint',
                'type' => 'statuses',
                'limit' => 5,
                'resolve' => true,
                'language' => 'en',
                'hasMedia' => true,
                'hasLinks' => true,
                'hasReplies' => true,
                'author' => '@analyst',
                'dateFrom' => '2026-06-01T10:00',
                'dateTo' => '2026-06-02T18:00',
                'instanceDomain' => 'mastodon.social',
            ]))
            ->assertOk()
            ->assertJsonPath('data.statuses.0.id', 'status-1')
            ->assertJsonPath('data.pagination.limit', 5);
    }

    public function test_mastodon_search_controller_maps_runtime_exception_to_json_error(): void
    {
        $user = $this->paidUser();

        $this->mock(MastodonSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('search')
                ->once()
                ->andThrow(new RuntimeException('Mastodon quota exceeded.', 429));
        });

        $this
            ->actingAs($user)
            ->getJson(route('mastodon.search.index', ['q' => 'osint']))
            ->assertTooManyRequests()
            ->assertJson([
                'ok' => false,
                'message' => 'Mastodon quota exceeded.',
            ]);
    }

    public function test_mastodon_search_controller_accepts_string_boolean_resolve_query_param(): void
    {
        $user = User::factory()->create();

        $this->mock(MastodonSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('search')
                ->once()
                ->with(Mockery::on(
                    fn (MastodonSearchQueryDTO $query): bool => $query->query === 'osint'
                        && $query->resolve === false
                        && $query->hasMedia === false
                        && $query->hasLinks === false
                        && $query->hasReplies === false
                ))
                ->andReturn(new MastodonSearchResultDTO(
                    statuses: [],
                    accounts: [],
                    hashtags: [],
                    pagination: [
                        'query' => 'osint',
                        'type' => 'statuses',
                        'limit' => 10,
                        'offset' => 0,
                        'nextOffset' => null,
                        'hasMore' => false,
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('mastodon.search.index', [
                'q' => 'osint',
                'resolve' => 'false',
                'hasMedia' => 'false',
                'hasLinks' => 'false',
                'hasReplies' => 'false',
            ]))
            ->assertOk()
            ->assertJsonPath('data.pagination.query', 'osint');
    }

    public function test_mastodon_status_context_controller_returns_thread_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(MastodonSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('context')
                ->once()
                ->with('116679508138293961')
                ->andReturn(new MastodonStatusContextResultDTO(
                    ancestors: [],
                    descendants: [[
                        'id' => 'reply-1',
                        'content' => 'First reply',
                    ]],
                    descendantsTree: [[
                        'id' => 'reply-1',
                        'content' => 'First reply',
                        'replies' => [[
                            'id' => 'reply-1-1',
                            'content' => 'Nested reply',
                            'replies' => [],
                        ]],
                    ]],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('mastodon.statuses.context', ['statusId' => '116679508138293961']))
            ->assertOk()
            ->assertJsonPath('data.descendants.0.id', 'reply-1')
            ->assertJsonPath('data.descendantsTree.0.replies.0.id', 'reply-1-1');
    }

    public function test_mastodon_account_statuses_controller_returns_statuses_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(MastodonSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('accountStatuses')
                ->once()
                ->with('109999', 10, null)
                ->andReturn(new \App\Modules\Mastodon\DTO\Result\MastodonAccountStatusesResultDTO(
                    statuses: [[
                        'id' => 'status-42',
                        'content' => 'Account status',
                    ]],
                    pagination: [
                        'limit' => 10,
                        'maxId' => null,
                        'nextMaxId' => null,
                        'hasMore' => false,
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('mastodon.accounts.statuses', ['accountId' => '109999', 'limit' => 10]))
            ->assertOk()
            ->assertJsonPath('data.statuses.0.id', 'status-42');
    }

    public function test_mastodon_account_followers_controller_returns_followers_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(MastodonSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('accountFollowers')
                ->once()
                ->with('109999', 10, null)
                ->andReturn(new \App\Modules\Mastodon\DTO\Result\MastodonAccountFollowersResultDTO(
                    accounts: [[
                        'id' => 'follower-1',
                        'acct' => 'analyst@example.social',
                    ]],
                    pagination: [
                        'limit' => 10,
                        'maxId' => null,
                        'nextMaxId' => null,
                        'hasMore' => false,
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('mastodon.accounts.followers', ['accountId' => '109999', 'limit' => 10]))
            ->assertOk()
            ->assertJsonPath('data.accounts.0.id', 'follower-1');
    }

    public function test_mastodon_tag_timeline_controller_returns_statuses_and_analytics_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(MastodonSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('tagTimeline')
                ->once()
                ->with('osint', 10, null)
                ->andReturn(new MastodonTagTimelineResultDTO(
                    statuses: [[
                        'id' => 'tag-status-1',
                        'content' => 'Tagged post',
                    ]],
                    analytics: [
                        'uniqueAccountsCount' => 1,
                        'uniqueAccounts' => [[
                            'id' => 'account-1',
                            'acct' => 'analyst@example.social',
                        ]],
                        'uniqueInstancesCount' => 1,
                        'instanceDomains' => ['example.social'],
                        'postsWithMediaCount' => 0,
                        'postsWithLinksCount' => 1,
                    ],
                    pagination: [
                        'limit' => 10,
                        'maxId' => null,
                        'nextMaxId' => null,
                        'hasMore' => false,
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('mastodon.tags.statuses', ['tagName' => 'osint', 'limit' => 10]))
            ->assertOk()
            ->assertJsonPath('data.statuses.0.id', 'tag-status-1')
            ->assertJsonPath('data.analytics.uniqueAccountsCount', 1);
    }

    public function test_mastodon_analytics_controller_returns_summary_payload(): void
    {
        $user = $this->paidUser();

        $this->mock(MastodonAnalyticsApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('summary')
                ->once()
                ->with(Mockery::on(
                    fn (MastodonAnalyticsQueryDTO $query): bool => $query->mode === 'hashtag'
                        && $query->target === 'osint'
                        && $query->limit === 10
                        && $query->pages === 3
                        && $query->dateFrom === '2026-05-01'
                        && $query->dateTo === '2026-05-31'
                        && $query->resolve === true
                ))
                ->andReturn(new MastodonAnalyticsResultDTO(
                    profile: [
                        'id' => 'tag-1',
                        'name' => 'osint',
                        'url' => 'https://mastodon.social/tags/osint',
                        'history' => [],
                    ],
                    meta: [
                        'mode' => 'hashtag',
                        'target' => 'osint',
                        'resolvedTarget' => '#osint',
                        'pagesRequested' => 3,
                        'pagesLoaded' => 2,
                        'sampledPosts' => 12,
                    ],
                    summary: [
                        'postsCount' => 12,
                        'uniqueAccountsCount' => 5,
                        'uniqueInstancesCount' => 3,
                        'uniqueLanguagesCount' => 2,
                        'postsWithMediaCount' => 4,
                        'postsWithLinksCount' => 6,
                        'replyPostsCount' => 1,
                        'boostPostsCount' => 2,
                        'sensitivePostsCount' => 0,
                        'totalReplies' => 10,
                        'totalReblogs' => 22,
                        'totalFavourites' => 31,
                    ],
                    timeline: [],
                    topDomains: [],
                    topTags: [],
                    topAccounts: [],
                    topMentions: [],
                    topLanguages: [],
                    topPosts: [],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('mastodon.analytics.summary', [
                'mode' => 'hashtag',
                'target' => 'osint',
                'limit' => 10,
                'pages' => 3,
                'dateFrom' => '2026-05-01',
                'dateTo' => '2026-05-31',
                'resolve' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('data.meta.resolvedTarget', '#osint')
            ->assertJsonPath('data.summary.postsCount', 12);
    }

    public function test_mastodon_analytics_report_renders_html_response(): void
    {
        $user = $this->paidUser();

        $this->mock(MastodonAnalyticsApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('summary')
                ->once()
                ->andReturn(new MastodonAnalyticsResultDTO(
                    profile: null,
                    meta: [
                        'mode' => 'account',
                        'target' => '@analyst@example.social',
                        'resolvedTarget' => '@analyst@example.social',
                        'pagesRequested' => 3,
                        'pagesLoaded' => 1,
                        'sampledPosts' => 4,
                    ],
                    summary: [
                        'postsCount' => 4,
                        'uniqueAccountsCount' => 1,
                        'uniqueInstancesCount' => 1,
                        'uniqueLanguagesCount' => 1,
                        'postsWithMediaCount' => 0,
                        'postsWithLinksCount' => 1,
                        'replyPostsCount' => 0,
                        'boostPostsCount' => 0,
                        'sensitivePostsCount' => 0,
                        'totalReplies' => 2,
                        'totalReblogs' => 3,
                        'totalFavourites' => 5,
                    ],
                    timeline: [],
                    topDomains: [],
                    topTags: [],
                    topAccounts: [],
                    topMentions: [],
                    topLanguages: [],
                    topPosts: [],
                ));
        });

        $this
            ->actingAs($user)
            ->get(route('mastodon.analytics.report', [
                'mode' => 'account',
                'target' => '@analyst@example.social',
                'locale' => 'en',
            ]))
            ->assertOk()
            ->assertSee('Mastodon Analytics Report');
    }
}
