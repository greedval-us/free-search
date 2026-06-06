<?php

namespace Tests\Unit;

use App\Modules\Bluesky\Actions\Request\SearchContentAction;
use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use App\Modules\Bluesky\DTO\Request\BlueskySearchQueryDTO;
use App\Modules\Bluesky\Presenters\BlueskyActorPresenter;
use App\Modules\Bluesky\Presenters\BlueskyPostPresenter;
use Tests\TestCase;

class BlueskySearchContentActionTest extends TestCase
{
    public function test_it_applies_local_filters_and_preserves_gateway_cursors(): void
    {
        $gateway = new FakeBlueskySearchGateway();
        $action = new SearchContentAction(
            $gateway,
            new BlueskyPostPresenter(new BlueskyActorPresenter()),
            new BlueskyActorPresenter(),
        );

        $result = $action->handle(new BlueskySearchQueryDTO(
            query: 'osint',
            type: 'all',
            limit: 10,
            cursor: 'cursor-1',
            sort: 'latest',
            language: 'en',
            author: 'analyst.bsky.social',
            domain: 'example.org',
            tag: 'osint',
            since: '2026-06-01T00:00:00Z',
            until: '2026-06-03T00:00:00Z',
        ));

        $this->assertSame('osint', $gateway->searchPostsCalls[0]['q']);
        $this->assertSame('latest', $gateway->searchPostsCalls[0]['sort']);
        $this->assertSame('cursor-1', $gateway->searchActorsCalls[0]['cursor']);

        $payload = $result->toArray();

        $this->assertCount(1, $payload['posts']);
        $this->assertSame('at://did:plc:alpha/app.bsky.feed.post/3lt1', $payload['posts'][0]['id']);
        $this->assertCount(1, $payload['actors']);
        $this->assertSame('actor-cursor-2', $payload['pagination']['actors']['nextCursor']);
        $this->assertSame('post-cursor-2', $payload['pagination']['posts']['nextCursor']);
    }
}

class FakeBlueskySearchGateway implements BlueskyGatewayInterface
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $searchPostsCalls = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $searchActorsCalls = [];

    public function searchPosts(array $params): array
    {
        $this->searchPostsCalls[] = $params;

        return [
            'cursor' => 'post-cursor-2',
            'posts' => [
                [
                    'uri' => 'at://did:plc:alpha/app.bsky.feed.post/3lt1',
                    'cid' => 'cid-1',
                    'indexedAt' => '2026-06-02T09:00:00Z',
                    'replyCount' => 2,
                    'repostCount' => 3,
                    'likeCount' => 4,
                    'quoteCount' => 1,
                    'author' => [
                        'did' => 'did:plc:alpha',
                        'handle' => 'analyst.bsky.social',
                        'displayName' => 'Analyst',
                    ],
                    'record' => [
                        'text' => 'OSINT link',
                        'createdAt' => '2026-06-02T08:00:00Z',
                        'langs' => ['en'],
                        'facets' => [
                            [
                                'features' => [
                                    [
                                        'uri' => 'https://example.org/report',
                                    ],
                                    [
                                        'tag' => 'osint',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'uri' => 'at://did:plc:beta/app.bsky.feed.post/3lt2',
                    'cid' => 'cid-2',
                    'indexedAt' => '2026-06-02T09:00:00Z',
                    'author' => [
                        'did' => 'did:plc:beta',
                        'handle' => 'other.bsky.social',
                        'displayName' => 'Other',
                    ],
                    'record' => [
                        'text' => 'Filtered out',
                        'createdAt' => '2026-06-02T08:00:00Z',
                        'langs' => ['ru'],
                        'facets' => [],
                    ],
                ],
            ],
        ];
    }

    public function searchActors(array $params): array
    {
        $this->searchActorsCalls[] = $params;

        return [
            'cursor' => 'actor-cursor-2',
            'actors' => [
                [
                    'did' => 'did:plc:alpha',
                    'handle' => 'analyst.bsky.social',
                    'displayName' => 'Analyst',
                    'description' => 'OSINT account',
                ],
            ],
        ];
    }

    public function getLikes(string $uri, ?string $cid, int $limit, ?string $cursor = null): array
    {
        return ['likes' => []];
    }

    public function getProfiles(array $actors): array
    {
        return ['profiles' => []];
    }

    public function getRepostedBy(string $uri, int $limit, ?string $cursor = null): array
    {
        return ['repostedBy' => []];
    }

    public function getPostThread(string $uri, int $depth = 6, int $parentHeight = 6): array
    {
        return ['thread' => []];
    }

    public function getAuthorFeed(string $actor, int $limit, ?string $cursor = null, ?string $filter = null): array
    {
        return ['feed' => []];
    }

    public function getFollowers(string $actor, int $limit, ?string $cursor = null): array
    {
        return ['followers' => []];
    }

    public function getFollows(string $actor, int $limit, ?string $cursor = null): array
    {
        return ['follows' => []];
    }
}
