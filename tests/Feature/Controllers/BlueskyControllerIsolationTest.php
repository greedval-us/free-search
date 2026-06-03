<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Modules\Bluesky\DTO\Request\BlueskySearchQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskySearchResultDTO;
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
}
