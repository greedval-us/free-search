<?php

namespace App\Modules\Mastodon\Analytics;

use App\Modules\Mastodon\Analytics\Contracts\MastodonAnalyticsApplicationServiceInterface;
use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\DTO\Request\MastodonAnalyticsQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAnalyticsResultDTO;
use App\Modules\Mastodon\Presenters\MastodonAccountPresenter;
use App\Modules\Mastodon\Presenters\MastodonHashtagPresenter;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;
use Illuminate\Support\Collection;
use RuntimeException;

final class MastodonAnalyticsApplicationService implements MastodonAnalyticsApplicationServiceInterface
{
    private const SEARCH_PICK_LIMIT = 10;
    private const TOP_POSTS_LIMIT = 8;
    private const TOP_VALUES_LIMIT = 10;

    public function __construct(
        private readonly MastodonGatewayInterface $gateway,
        private readonly MastodonStatusPresenter $statusPresenter,
        private readonly MastodonAccountPresenter $accountPresenter,
        private readonly MastodonHashtagPresenter $hashtagPresenter,
    ) {
    }

    public function summary(MastodonAnalyticsQueryDTO $query): MastodonAnalyticsResultDTO
    {
        if ($query->mode === 'account') {
            return $this->buildAccountSummary($query);
        }

        return $this->buildHashtagSummary($query);
    }

    private function buildAccountSummary(MastodonAnalyticsQueryDTO $query): MastodonAnalyticsResultDTO
    {
        $resolvedAccount = $this->resolveAccount($query->target, $query->resolve);
        $accountId = (string) ($resolvedAccount['id'] ?? '');

        if ($accountId === '') {
            throw new RuntimeException('Mastodon account was not found.', 404);
        }

        [$statuses, $pagesLoaded] = $this->collectStatuses(
            fetch: fn (?string $maxId): array => $this->gateway->accountStatuses($accountId, $query->limit, $maxId),
            pages: $query->pages,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
        );

        return $this->buildResult(
            mode: 'account',
            target: $query->target,
            profile: $resolvedAccount,
            statuses: $statuses,
            pagesRequested: $query->pages,
            pagesLoaded: $pagesLoaded,
        );
    }

    private function buildHashtagSummary(MastodonAnalyticsQueryDTO $query): MastodonAnalyticsResultDTO
    {
        $resolvedTag = $this->resolveHashtag($query->target, $query->resolve);
        $tagName = (string) ($resolvedTag['name'] ?? trim(ltrim($query->target, '#')));

        if ($tagName === '') {
            throw new RuntimeException('Mastodon hashtag was not found.', 404);
        }

        [$statuses, $pagesLoaded] = $this->collectStatuses(
            fetch: fn (?string $maxId): array => $this->gateway->tagTimeline($tagName, $query->limit, $maxId),
            pages: $query->pages,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
        );

        return $this->buildResult(
            mode: 'hashtag',
            target: $query->target,
            profile: $resolvedTag !== [] ? $resolvedTag : [
                'id' => '',
                'name' => $tagName,
                'url' => '',
                'history' => [],
            ],
            statuses: $statuses,
            pagesRequested: $query->pages,
            pagesLoaded: $pagesLoaded,
        );
    }

    /**
     * @param callable(?string): array<string, mixed> $fetch
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function collectStatuses(callable $fetch, int $pages, string $dateFrom, string $dateTo): array
    {
        $statuses = [];
        $maxId = null;
        $pagesLoaded = 0;

        for ($page = 0; $page < $pages; $page++) {
            $payload = $fetch($maxId);
            $rawItems = collect($payload['items'] ?? []);

            if ($rawItems->isEmpty()) {
                break;
            }

            $statuses = [
                ...$statuses,
                ...$rawItems
                    ->map(fn (array $item): array => $this->statusPresenter->present($item))
                    ->all(),
            ];

            $pagesLoaded++;
            $nextMaxId = data_get($payload, 'pagination.nextMaxId');

            if (! is_string($nextMaxId) || $nextMaxId === '') {
                break;
            }

            $maxId = $nextMaxId;
        }

        return [
            $this->filterStatusesByDate($statuses, $dateFrom, $dateTo),
            $pagesLoaded,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $statuses
     * @return array<int, array<string, mixed>>
     */
    private function filterStatusesByDate(array $statuses, string $dateFrom, string $dateTo): array
    {
        if ($dateFrom === '' && $dateTo === '') {
            return array_values($statuses);
        }

        $fromTimestamp = $dateFrom !== '' ? strtotime($dateFrom) : null;
        $toTimestamp = $dateTo !== '' ? strtotime($dateTo.' 23:59:59') : null;

        return array_values(array_filter($statuses, function (array $status) use ($fromTimestamp, $toTimestamp): bool {
            $createdAt = (string) ($status['createdAt'] ?? '');
            $timestamp = strtotime($createdAt);

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
     * @param array<int, array<string, mixed>> $statuses
     */
    private function buildResult(
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

        $profileTarget = $this->buildResolvedTarget($mode, $profile, $target);

        return new MastodonAnalyticsResultDTO(
            profile: $profile,
            meta: [
                'mode' => $mode,
                'target' => $target,
                'resolvedTarget' => $profileTarget,
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
     * @return array<string, mixed>
     */
    private function resolveAccount(string $target, bool $resolve): array
    {
        $needle = strtolower(trim($target));

        if ($needle === '') {
            return [];
        }

        $payload = $this->gateway->search($this->searchPayload($target, 'accounts', $resolve));

        $accounts = collect($payload['accounts'] ?? [])
            ->map(fn (array $item): array => $this->accountPresenter->present($item));

        $exact = $accounts->first(fn (array $account): bool => $this->matchesAccountTarget($account, $needle));

        if (is_array($exact)) {
            return $exact;
        }

        return $accounts->first() ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveHashtag(string $target, bool $resolve): array
    {
        $needle = strtolower(trim(ltrim($target, '#')));

        if ($needle === '') {
            return [];
        }

        $payload = $this->gateway->search($this->searchPayload($needle, 'hashtags', $resolve));

        $hashtags = collect($payload['hashtags'] ?? [])
            ->map(fn (array $item): array => $this->hashtagPresenter->present($item));

        $exact = $hashtags->first(
            fn (array $tag): bool => strtolower((string) ($tag['name'] ?? '')) === $needle
        );

        if (is_array($exact)) {
            return $exact;
        }

        return $hashtags->first() ?? [];
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
     * @return array<string, mixed>
     */
    private function searchPayload(string $query, string $type, bool $resolve): array
    {
        return array_filter([
            'q' => $query,
            'type' => $type,
            'limit' => self::SEARCH_PICK_LIMIT,
            'resolve' => $resolve ? 'true' : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @param array<string, mixed> $account
     */
    private function matchesAccountTarget(array $account, string $needle): bool
    {
        $acct = strtolower((string) ($account['acct'] ?? ''));
        $username = strtolower((string) ($account['username'] ?? ''));
        $url = strtolower((string) ($account['url'] ?? ''));

        return $acct === ltrim($needle, '@')
            || '@'.$acct === $needle
            || $username === ltrim($needle, '@')
            || $url === $needle;
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
