<?php

namespace App\Modules\Bluesky\Analytics;

use App\Modules\Bluesky\Analytics\Contracts\BlueskyAnalyticsApplicationServiceInterface;
use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use App\Modules\Bluesky\DTO\Request\BlueskyAnalyticsQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyAnalyticsResultDTO;
use App\Modules\Bluesky\Presenters\BlueskyActorPresenter;
use App\Modules\Bluesky\Presenters\BlueskyPostPresenter;
use App\Modules\Bluesky\Support\BlueskyActorResolver;
use Illuminate\Support\Collection;
use RuntimeException;

final class BlueskyAnalyticsApplicationService implements BlueskyAnalyticsApplicationServiceInterface
{
    private const TOP_POSTS_LIMIT = 8;
    private const TOP_VALUES_LIMIT = 10;

    public function __construct(
        private readonly BlueskyGatewayInterface $gateway,
        private readonly BlueskyPostPresenter $postPresenter,
        private readonly BlueskyActorPresenter $actorPresenter,
        private readonly BlueskyActorResolver $actorResolver,
    ) {
    }

    public function summary(BlueskyAnalyticsQueryDTO $query): BlueskyAnalyticsResultDTO
    {
        return $query->mode === 'hashtag'
            ? $this->buildHashtagSummary($query)
            : $this->buildAccountSummary($query);
    }

    private function buildAccountSummary(BlueskyAnalyticsQueryDTO $query): BlueskyAnalyticsResultDTO
    {
        $profile = $this->resolveAccount($query->target, $query->resolve);
        $actor = (string) ($profile['handle'] ?? $profile['did'] ?? '');

        if ($actor === '') {
            throw new RuntimeException('Bluesky account was not found.', 404);
        }

        [$posts, $pagesLoaded] = $this->collectAuthorFeedPosts(
            actor: $actor,
            limit: $query->limit,
            pages: $query->pages,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
        );

        return $this->buildResult(
            mode: 'account',
            target: $query->target,
            profile: $profile,
            posts: $posts,
            pagesRequested: $query->pages,
            pagesLoaded: $pagesLoaded,
        );
    }

    private function buildHashtagSummary(BlueskyAnalyticsQueryDTO $query): BlueskyAnalyticsResultDTO
    {
        $tag = ltrim(trim($query->target), '#');

        if ($tag === '') {
            throw new RuntimeException('Bluesky hashtag was not found.', 404);
        }

        [$posts, $pagesLoaded] = $this->collectTagPosts(
            tag: $tag,
            limit: $query->limit,
            pages: $query->pages,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
        );

        return $this->buildResult(
            mode: 'hashtag',
            target: $query->target,
            profile: [
                'id' => strtolower($tag),
                'name' => $tag,
                'url' => sprintf('https://bsky.app/search?q=%s', rawurlencode('#'.$tag)),
                'history' => [],
            ],
            posts: $posts,
            pagesRequested: $query->pages,
            pagesLoaded: $pagesLoaded,
        );
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function collectAuthorFeedPosts(
        string $actor,
        int $limit,
        int $pages,
        string $dateFrom,
        string $dateTo,
    ): array {
        $posts = [];
        $cursor = null;
        $pagesLoaded = 0;

        for ($page = 0; $page < $pages; $page++) {
            $payload = $this->gateway->getAuthorFeed($actor, $limit, $cursor);
            $items = collect($payload['feed'] ?? [])
                ->map(function (array $item): ?array {
                    $post = (array) ($item['post'] ?? []);

                    return $post !== [] ? $this->postPresenter->present($post) : null;
                })
                ->filter()
                ->values();

            if ($items->isEmpty()) {
                break;
            }

            $posts = [...$posts, ...$items->all()];
            $pagesLoaded++;
            $cursor = $this->normalizedCursor($payload['cursor'] ?? null);

            if ($cursor === null) {
                break;
            }
        }

        return [$this->filterPostsByDate($posts, $dateFrom, $dateTo), $pagesLoaded];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function collectTagPosts(
        string $tag,
        int $limit,
        int $pages,
        string $dateFrom,
        string $dateTo,
    ): array {
        $posts = [];
        $cursor = null;
        $pagesLoaded = 0;

        for ($page = 0; $page < $pages; $page++) {
            $payload = $this->gateway->searchPosts(array_filter([
                'q' => $tag,
                'sort' => 'latest',
                'limit' => $limit,
                'cursor' => $cursor,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''));

            $items = collect($payload['posts'] ?? [])
                ->map(fn (array $item): array => $this->postPresenter->present($item))
                ->filter(fn (array $post): bool => collect($post['hashtags'] ?? [])
                    ->map(fn (mixed $value): string => strtolower(ltrim((string) $value, '#')))
                    ->contains(strtolower($tag)))
                ->values();

            if ($items->isEmpty()) {
                break;
            }

            $posts = [...$posts, ...$items->all()];
            $pagesLoaded++;
            $cursor = $this->normalizedCursor($payload['cursor'] ?? null);

            if ($cursor === null) {
                break;
            }
        }

        return [$this->filterPostsByDate($posts, $dateFrom, $dateTo), $pagesLoaded];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAccount(string $target, bool $resolve): array
    {
        return $this->actorResolver->resolve($target, $resolve);
    }

    /**
     * @param array<int, array<string, mixed>> $posts
     * @return array<int, array<string, mixed>>
     */
    private function filterPostsByDate(array $posts, string $dateFrom, string $dateTo): array
    {
        if ($dateFrom === '' && $dateTo === '') {
            return array_values($posts);
        }

        $fromTimestamp = $dateFrom !== '' ? strtotime($dateFrom) : null;
        $toTimestamp = $dateTo !== '' ? strtotime($dateTo.' 23:59:59') : null;

        return array_values(array_filter($posts, function (array $post) use ($fromTimestamp, $toTimestamp): bool {
            $timestamp = strtotime((string) ($post['createdAt'] ?? ''));

            if ($timestamp === false) {
                return false;
            }

            if ($fromTimestamp !== null && $timestamp < $fromTimestamp) {
                return false;
            }

            if ($toTimestamp !== null && $timestamp > $toTimestamp) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $posts
     */
    private function buildResult(
        string $mode,
        string $target,
        ?array $profile,
        array $posts,
        int $pagesRequested,
        int $pagesLoaded,
    ): BlueskyAnalyticsResultDTO {
        $postCollection = collect($posts);

        return new BlueskyAnalyticsResultDTO(
            profile: $profile,
            meta: [
                'mode' => $mode,
                'target' => $target,
                'resolvedTarget' => $this->buildResolvedTarget($mode, $profile, $target),
                'pagesRequested' => $pagesRequested,
                'pagesLoaded' => $pagesLoaded,
                'sampledPosts' => $postCollection->count(),
            ],
            summary: [
                'postsCount' => $postCollection->count(),
                'uniqueAuthorsCount' => $postCollection->pluck('author.did')->filter()->unique()->count(),
                'uniqueLanguagesCount' => $postCollection->pluck('languages')->flatten(1)->filter()->unique()->count(),
                'postsWithMediaCount' => $postCollection->where('hasMedia', true)->count(),
                'postsWithLinksCount' => $postCollection->where('hasLinks', true)->count(),
                'replyPostsCount' => $postCollection->where('postType', 'reply')->count(),
                'totalReplies' => $postCollection->sum('replyCount'),
                'totalReposts' => $postCollection->sum('repostCount'),
                'totalLikes' => $postCollection->sum('likeCount'),
                'totalQuotes' => $postCollection->sum('quoteCount'),
            ],
            timeline: $this->buildTimeline($postCollection),
            topDomains: $this->countScalarValues($postCollection->pluck('domains')->flatten(1), 'domain'),
            topTags: $this->countScalarValues($postCollection->pluck('hashtags')->flatten(1), 'tag'),
            topAuthors: $this->buildTopAuthors($postCollection),
            topMentions: $this->buildTopMentions($postCollection),
            topLanguages: $this->countScalarValues($postCollection->pluck('languages')->flatten(1), 'language'),
            topPosts: $postCollection
                ->sortByDesc(fn (array $post): int => (int) ($post['replyCount'] ?? 0)
                    + (int) ($post['repostCount'] ?? 0)
                    + (int) ($post['likeCount'] ?? 0)
                    + (int) ($post['quoteCount'] ?? 0))
                ->take(self::TOP_POSTS_LIMIT)
                ->values()
                ->all(),
        );
    }

    /**
     * @param Collection<int, array<string, mixed>> $posts
     * @return array<int, array<string, mixed>>
     */
    private function buildTimeline(Collection $posts): array
    {
        return $posts
            ->groupBy(function (array $post): string {
                $timestamp = strtotime((string) ($post['createdAt'] ?? ''));

                return $timestamp === false ? 'unknown' : gmdate('Y-m-d', $timestamp);
            })
            ->map(function (Collection $items, string $day): array {
                return [
                    'day' => $day,
                    'posts' => $items->count(),
                    'postsWithMedia' => $items->where('hasMedia', true)->count(),
                    'postsWithLinks' => $items->where('hasLinks', true)->count(),
                    'replies' => $items->sum('replyCount'),
                    'reposts' => $items->sum('repostCount'),
                    'likes' => $items->sum('likeCount'),
                    'quotes' => $items->sum('quoteCount'),
                ];
            })
            ->sortBy('day')
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, mixed> $values
     * @return array<int, array<string, mixed>>
     */
    private function countScalarValues(Collection $values, string $key): array
    {
        return $values
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(self::TOP_VALUES_LIMIT)
            ->map(fn (int $count, string $value): array => [
                $key => $value,
                'count' => $count,
            ])
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, array<string, mixed>> $posts
     * @return array<int, array<string, mixed>>
     */
    private function buildTopAuthors(Collection $posts): array
    {
        return $posts
            ->groupBy('author.did')
            ->map(function (Collection $items): array {
                /** @var array<string, mixed> $author */
                $author = (array) ($items->first()['author'] ?? []);

                return [
                    ...$author,
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(self::TOP_VALUES_LIMIT)
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, array<string, mixed>> $posts
     * @return array<int, array<string, mixed>>
     */
    private function buildTopMentions(Collection $posts): array
    {
        $topMentionCounts = $posts
            ->pluck('mentions')
            ->flatten(1)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(self::TOP_VALUES_LIMIT);

        if ($topMentionCounts->isEmpty()) {
            return [];
        }

        $profiles = collect($this->gateway->getProfiles($topMentionCounts->keys()->all())['profiles'] ?? [])
            ->map(fn (array $item): array => $this->actorPresenter->present($item))
            ->keyBy(fn (array $item): string => (string) ($item['did'] ?? ''));

        return $topMentionCounts
            ->map(function (int $count, string $did) use ($profiles): array {
                $profile = (array) ($profiles->get($did) ?? []);

                return [
                    'did' => $did,
                    'handle' => (string) ($profile['handle'] ?? ''),
                    'displayName' => (string) ($profile['displayName'] ?? ''),
                    'url' => (string) ($profile['url'] ?? ''),
                    'count' => $count,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed>|null $profile
     */
    private function buildResolvedTarget(string $mode, ?array $profile, string $target): string
    {
        if ($mode === 'account') {
            return (string) ($profile['handle'] ?? $profile['did'] ?? $target);
        }

        return '#'.(string) ($profile['name'] ?? ltrim(trim($target), '#'));
    }

    private function normalizedCursor(mixed $value): ?string
    {
        $cursor = trim((string) $value);

        return $cursor !== '' ? $cursor : null;
    }
}
