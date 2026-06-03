<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Modules\Bluesky\DTO\Request\BlueskySearchQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyActorListResultDTO;
use App\Modules\Bluesky\DTO\Result\BlueskySearchResultDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyThreadResultDTO;
use App\Modules\Bluesky\Search\Contracts\BlueskySearchApplicationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class BlueskyControllerIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bluesky_search_controller_returns_service_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(BlueskySearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('search')
                ->once()
                ->with(Mockery::on(
                    fn (BlueskySearchQueryDTO $query): bool => $query->query === 'osint'
                        && $query->type === 'posts'
                        && $query->limit === 5
                        && $query->cursor === 'cursor-1'
                        && $query->sort === 'latest'
                        && $query->language === 'en'
                        && $query->author === 'analyst'
                        && $query->mentions === 'did:plc:friend'
                        && $query->domain === 'example.org'
                        && $query->url === 'https://example.org/report'
                        && $query->tag === 'investigation'
                        && $query->since === '2026-06-01T00:00'
                        && $query->until === '2026-06-02T23:59'
                ))
                ->andReturn(new BlueskySearchResultDTO(
                    posts: [[
                        'id' => 'at://did:plc:alpha/app.bsky.feed.post/3lt1',
                        'text' => 'OSINT on Bluesky',
                    ]],
                    actors: [],
                    meta: [
                        'query' => 'osint',
                        'type' => 'posts',
                        'limit' => 5,
                        'sort' => 'latest',
                    ],
                    pagination: [
                        'cursor' => 'cursor-1',
                        'posts' => [
                            'nextCursor' => 'cursor-2',
                            'hasMore' => true,
                        ],
                        'actors' => [
                            'nextCursor' => null,
                            'hasMore' => false,
                        ],
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('bluesky.search.index', [
                'q' => 'osint',
                'type' => 'posts',
                'limit' => 5,
                'cursor' => 'cursor-1',
                'sort' => 'latest',
                'language' => 'en',
                'author' => 'analyst',
                'mentions' => '@did:plc:friend',
                'domain' => 'Example.org',
                'url' => 'https://example.org/report',
                'tag' => '#investigation',
                'since' => '2026-06-01T00:00',
                'until' => '2026-06-02T23:59',
            ]))
            ->assertOk()
            ->assertJsonPath('data.posts.0.id', 'at://did:plc:alpha/app.bsky.feed.post/3lt1')
            ->assertJsonPath('data.pagination.posts.nextCursor', 'cursor-2');
    }

    public function test_bluesky_search_controller_maps_runtime_exception_to_json_error(): void
    {
        $user = User::factory()->create();

        $this->mock(BlueskySearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('search')
                ->once()
                ->andThrow(new RuntimeException('Bluesky rate limit exceeded.', 429));
        });

        $this
            ->actingAs($user)
            ->getJson(route('bluesky.search.index', ['q' => 'osint']))
            ->assertTooManyRequests()
            ->assertJson([
                'ok' => false,
                'message' => 'Bluesky rate limit exceeded.',
            ]);
    }

    public function test_bluesky_likes_controller_returns_service_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(BlueskySearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('likes')
                ->once()
                ->with('at://did:plc:alpha/app.bsky.feed.post/3lt1', 'cid-1', 10, null)
                ->andReturn(new BlueskyActorListResultDTO(
                    items: [[
                        'actor' => [
                            'did' => 'did:plc:fan',
                            'handle' => 'fan.bsky.social',
                        ],
                        'createdAt' => '2026-06-03T10:00:00Z',
                        'indexedAt' => '2026-06-03T10:00:01Z',
                    ]],
                    pagination: [
                        'limit' => 10,
                        'cursor' => null,
                        'nextCursor' => null,
                        'hasMore' => false,
                    ],
                    meta: [
                        'uri' => 'at://did:plc:alpha/app.bsky.feed.post/3lt1',
                        'kind' => 'likes',
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('bluesky.posts.likes', [
                'uri' => 'at://did:plc:alpha/app.bsky.feed.post/3lt1',
                'cid' => 'cid-1',
                'limit' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('data.items.0.actor.handle', 'fan.bsky.social');
    }

    public function test_bluesky_reposts_controller_returns_service_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(BlueskySearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('reposts')
                ->once()
                ->with('at://did:plc:alpha/app.bsky.feed.post/3lt1', 10, null)
                ->andReturn(new BlueskyActorListResultDTO(
                    items: [[
                        'actor' => [
                            'did' => 'did:plc:boost',
                            'handle' => 'booster.bsky.social',
                        ],
                        'createdAt' => '',
                        'indexedAt' => '',
                    ]],
                    pagination: [
                        'limit' => 10,
                        'cursor' => null,
                        'nextCursor' => null,
                        'hasMore' => false,
                    ],
                    meta: [
                        'uri' => 'at://did:plc:alpha/app.bsky.feed.post/3lt1',
                        'kind' => 'reposts',
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('bluesky.posts.reposts', [
                'uri' => 'at://did:plc:alpha/app.bsky.feed.post/3lt1',
                'limit' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('data.items.0.actor.handle', 'booster.bsky.social');
    }

    public function test_bluesky_thread_controller_returns_service_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(BlueskySearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('thread')
                ->once()
                ->with('at://did:plc:alpha/app.bsky.feed.post/3lt1', 4, 3)
                ->andReturn(new BlueskyThreadResultDTO(
                    root: [
                        'id' => 'at://did:plc:alpha/app.bsky.feed.post/3lt1',
                        'text' => 'Root post',
                        'replies' => [],
                    ],
                    ancestors: [[
                        'id' => 'at://did:plc:parent/app.bsky.feed.post/1',
                        'text' => 'Parent post',
                    ]],
                    replies: [[
                        'id' => 'at://did:plc:reply/app.bsky.feed.post/2',
                        'text' => 'Reply post',
                        'replies' => [],
                    ]],
                    meta: [
                        'uri' => 'at://did:plc:alpha/app.bsky.feed.post/3lt1',
                        'depth' => 4,
                        'parentHeight' => 3,
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('bluesky.posts.thread', [
                'uri' => 'at://did:plc:alpha/app.bsky.feed.post/3lt1',
                'depth' => 4,
                'parentHeight' => 3,
            ]))
            ->assertOk()
            ->assertJsonPath('data.ancestors.0.id', 'at://did:plc:parent/app.bsky.feed.post/1')
            ->assertJsonPath('data.replies.0.id', 'at://did:plc:reply/app.bsky.feed.post/2');
    }
}
