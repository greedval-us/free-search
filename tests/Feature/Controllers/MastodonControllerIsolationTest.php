<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonSearchResultDTO;
use App\Modules\Mastodon\DTO\Result\MastodonStatusContextResultDTO;
use App\Modules\Mastodon\Search\Contracts\MastodonSearchApplicationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MastodonControllerIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mastodon_search_controller_returns_service_payload(): void
    {
        $user = User::factory()->create();

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
                'instanceDomain' => 'mastodon.social',
            ]))
            ->assertOk()
            ->assertJsonPath('data.statuses.0.id', 'status-1')
            ->assertJsonPath('data.pagination.limit', 5);
    }

    public function test_mastodon_search_controller_maps_runtime_exception_to_json_error(): void
    {
        $user = User::factory()->create();

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
}
