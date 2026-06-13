<?php

namespace App\Modules\Bluesky\Analytics;

use App\Exceptions\PublicException;
use App\Modules\Bluesky\Analytics\Contracts\BlueskyAnalyticsApplicationServiceInterface;
use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use App\Modules\Bluesky\DTO\Request\BlueskyAnalyticsQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyAnalyticsResultDTO;
use App\Modules\Bluesky\Presenters\BlueskyActorPresenter;
use App\Modules\Bluesky\Presenters\BlueskyPostPresenter;
use App\Modules\Bluesky\Support\BlueskyActorResolver;
use Illuminate\Support\Collection;

final class BlueskyAnalyticsApplicationService implements BlueskyAnalyticsApplicationServiceInterface
{
    private const TOP_VALUES_LIMIT = 10;

    public function __construct(
        private readonly BlueskyGatewayInterface $gateway,
        private readonly BlueskyPostPresenter $postPresenter,
        private readonly BlueskyActorPresenter $actorPresenter,
        private readonly BlueskyActorResolver $actorResolver,
        private readonly BlueskyAnalyticsReportBuilder $reportBuilder,
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
            throw new PublicException('errors.api.bluesky.account_not_found', 404, 'bluesky_account_not_found');
        }

        [$posts, $pagesLoaded] = $this->collectAuthorFeedPosts(
            actor: $actor,
            limit: $query->limit,
            pages: $query->pages,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
        );

        return $this->reportBuilder->build(
            mode: 'account',
            target: $query->target,
            profile: $profile,
            posts: $posts,
            pagesRequested: $query->pages,
            pagesLoaded: $pagesLoaded,
            topMentions: $this->buildTopMentions(collect($posts)),
        );
    }

    private function buildHashtagSummary(BlueskyAnalyticsQueryDTO $query): BlueskyAnalyticsResultDTO
    {
        $tag = ltrim(trim($query->target), '#');

        if ($tag === '') {
            throw new PublicException('errors.api.bluesky.hashtag_not_found', 404, 'bluesky_hashtag_not_found');
        }

        [$posts, $pagesLoaded] = $this->collectTagPosts(
            tag: $tag,
            limit: $query->limit,
            pages: $query->pages,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
        );

        return $this->reportBuilder->build(
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
            topMentions: $this->buildTopMentions(collect($posts)),
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

    private function normalizedCursor(mixed $value): ?string
    {
        $cursor = trim((string) $value);

        return $cursor !== '' ? $cursor : null;
    }
}
