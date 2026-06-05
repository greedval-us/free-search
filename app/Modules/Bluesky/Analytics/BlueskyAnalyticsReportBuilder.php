<?php

namespace App\Modules\Bluesky\Analytics;

use App\Modules\Bluesky\DTO\Result\BlueskyAnalyticsResultDTO;
use Illuminate\Support\Collection;

final class BlueskyAnalyticsReportBuilder
{
    private const TOP_POSTS_LIMIT = 8;
    private const TOP_VALUES_LIMIT = 10;

    /**
     * @param array<int, array<string, mixed>> $posts
     * @param array<string, mixed>|null $profile
     */
    public function build(
        string $mode,
        string $target,
        ?array $profile,
        array $posts,
        int $pagesRequested,
        int $pagesLoaded,
        ?array $topMentions = null,
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
            topMentions: $topMentions ?? $this->buildTopMentions($postCollection),
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
        return $posts
            ->pluck('mentions')
            ->flatten(1)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(self::TOP_VALUES_LIMIT)
            ->map(fn (int $count, string $did): array => [
                'did' => $did,
                'count' => $count,
            ])
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
}
