<?php

namespace App\Modules\Mastodon\Analytics;

use App\Modules\Mastodon\DTO\Result\MastodonAnalyticsResultDTO;
use Illuminate\Support\Collection;

final class MastodonAnalyticsReportBuilder
{
    private const TOP_POSTS_LIMIT = 8;
    private const TOP_VALUES_LIMIT = 10;

    /**
     * @param array<int, array<string, mixed>> $statuses
     * @param array<string, mixed>|null $profile
     */
    public function build(
        string $mode,
        string $target,
        ?array $profile,
        array $statuses,
        int $pagesRequested,
        int $pagesLoaded,
    ): MastodonAnalyticsResultDTO {
        $statusCollection = collect($statuses);
        $topPosts = $statusCollection
            ->sortByDesc(fn (array $status): int => ((int) ($status['repliesCount'] ?? 0))
                + ((int) ($status['reblogsCount'] ?? 0))
                + ((int) ($status['favouritesCount'] ?? 0)))
            ->take(self::TOP_POSTS_LIMIT)
            ->values()
            ->all();

        return new MastodonAnalyticsResultDTO(
            profile: $profile,
            meta: [
                'mode' => $mode,
                'target' => $target,
                'resolvedTarget' => $this->buildResolvedTarget($mode, $profile, $target),
                'pagesRequested' => $pagesRequested,
                'pagesLoaded' => $pagesLoaded,
                'sampledPosts' => $statusCollection->count(),
            ],
            summary: [
                'postsCount' => $statusCollection->count(),
                'uniqueAccountsCount' => $statusCollection
                    ->pluck('account.id')
                    ->filter()
                    ->unique()
                    ->count(),
                'uniqueInstancesCount' => $statusCollection
                    ->pluck('instanceDomain')
                    ->filter()
                    ->unique()
                    ->count(),
                'uniqueLanguagesCount' => $statusCollection
                    ->pluck('language')
                    ->filter()
                    ->unique()
                    ->count(),
                'postsWithMediaCount' => $statusCollection->where('hasMedia', true)->count(),
                'postsWithLinksCount' => $statusCollection->where('hasLinks', true)->count(),
                'replyPostsCount' => $statusCollection->where('postType', 'reply')->count(),
                'boostPostsCount' => $statusCollection->where('postType', 'boost')->count(),
                'sensitivePostsCount' => $statusCollection->where('sensitive', true)->count(),
                'totalReplies' => $statusCollection->sum('repliesCount'),
                'totalReblogs' => $statusCollection->sum('reblogsCount'),
                'totalFavourites' => $statusCollection->sum('favouritesCount'),
            ],
            timeline: $this->buildTimeline($statusCollection),
            topDomains: $this->countScalarValues($statusCollection->pluck('domains')->flatten(1), 'domain'),
            topTags: $this->countScalarValues($statusCollection->pluck('tags')->flatten(1), 'tag'),
            topAccounts: $this->buildTopAccounts($statusCollection),
            topMentions: $this->buildTopMentions($statusCollection),
            topLanguages: $this->countScalarValues($statusCollection->pluck('language'), 'language'),
            topPosts: $topPosts,
        );
    }

    /**
     * @param Collection<int, array<string, mixed>> $statuses
     * @return array<int, array<string, mixed>>
     */
    private function buildTimeline(Collection $statuses): array
    {
        return $statuses
            ->groupBy(function (array $status): string {
                $createdAt = (string) ($status['createdAt'] ?? '');
                $timestamp = strtotime($createdAt);

                return $timestamp === false ? 'unknown' : gmdate('Y-m-d', $timestamp);
            })
            ->map(function (Collection $items, string $day): array {
                return [
                    'day' => $day,
                    'posts' => $items->count(),
                    'postsWithMedia' => $items->where('hasMedia', true)->count(),
                    'postsWithLinks' => $items->where('hasLinks', true)->count(),
                    'replies' => $items->sum('repliesCount'),
                    'reblogs' => $items->sum('reblogsCount'),
                    'favourites' => $items->sum('favouritesCount'),
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
     * @param Collection<int, array<string, mixed>> $statuses
     * @return array<int, array<string, mixed>>
     */
    private function buildTopAccounts(Collection $statuses): array
    {
        return $statuses
            ->groupBy('account.id')
            ->map(function (Collection $items): array {
                /** @var array<string, mixed> $account */
                $account = (array) ($items->first()['account'] ?? []);

                return [
                    ...$account,
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(self::TOP_VALUES_LIMIT)
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, array<string, mixed>> $statuses
     * @return array<int, array<string, mixed>>
     */
    private function buildTopMentions(Collection $statuses): array
    {
        return $statuses
            ->pluck('mentions')
            ->flatten(1)
            ->filter(fn (mixed $mention): bool => is_array($mention) && (string) ($mention['acct'] ?? '') !== '')
            ->groupBy(fn (array $mention): string => (string) ($mention['acct'] ?? ''))
            ->map(function (Collection $items): array {
                /** @var array<string, mixed> $mention */
                $mention = (array) $items->first();

                return [
                    'id' => (string) ($mention['id'] ?? ''),
                    'username' => (string) ($mention['username'] ?? ''),
                    'acct' => (string) ($mention['acct'] ?? ''),
                    'url' => (string) ($mention['url'] ?? ''),
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(self::TOP_VALUES_LIMIT)
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed>|null $profile
     */
    private function buildResolvedTarget(string $mode, ?array $profile, string $target): string
    {
        if ($mode === 'account') {
            return (string) ($profile['acct'] ?? $target);
        }

        return '#'.(string) ($profile['name'] ?? trim(ltrim($target, '#')));
    }
}
