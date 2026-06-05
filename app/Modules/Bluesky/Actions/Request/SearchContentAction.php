<?php

namespace App\Modules\Bluesky\Actions\Request;

use App\Modules\Bluesky\Actions\AbstractBlueskyAction;
use App\Modules\Bluesky\DTO\Request\BlueskySearchQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskySearchResultDTO;
use App\Modules\Bluesky\Presenters\BlueskyActorPresenter;
use App\Modules\Bluesky\Presenters\BlueskyPostPresenter;

final class SearchContentAction extends AbstractBlueskyAction
{
    public function __construct(
        \App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface $gateway,
        private readonly BlueskyPostPresenter $postPresenter,
        private readonly BlueskyActorPresenter $actorPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(BlueskySearchQueryDTO $query): BlueskySearchResultDTO
    {
        $postsPayload = $query->includesPosts()
            ? $this->gateway->searchPosts($query->toPostParams())
            : [];

        $actorsPayload = $query->includesActors()
            ? $this->gateway->searchActors($query->toActorParams())
            : [];

        $posts = collect($postsPayload['posts'] ?? [])
            ->map(fn (array $item): array => $this->postPresenter->present($item))
            ->filter(fn (array $item): bool => $this->matchesPostFilters($item, $query))
            ->values()
            ->all();

        $actors = collect($actorsPayload['actors'] ?? [])
            ->pipe(function ($actors) {
                $identifiers = $actors
                    ->map(fn (array $item): string => (string) ($item['did'] ?? $item['handle'] ?? ''))
                    ->filter()
                    ->values()
                    ->all();

                if ($identifiers === []) {
                    return collect();
                }

                $profiles = collect($this->gateway->getProfiles($identifiers)['profiles'] ?? [])
                    ->keyBy(fn (array $item): string => (string) ($item['did'] ?? $item['handle'] ?? ''));

                return $actors->map(function (array $item) use ($profiles): array {
                    $key = (string) ($item['did'] ?? $item['handle'] ?? '');
                    $detailed = (array) ($profiles->get($key) ?? []);

                    return $this->actorPresenter->present($detailed !== [] ? array_replace($item, $detailed) : $item);
                });
            })
            ->values()
            ->all();

        $postsNextCursor = $this->normalizedCursor($postsPayload['cursor'] ?? null);
        $actorsNextCursor = $this->normalizedCursor($actorsPayload['cursor'] ?? null);

        return new BlueskySearchResultDTO(
            posts: $posts,
            actors: $actors,
            meta: [
                'query' => $query->query,
                'type' => $query->type,
                'limit' => $query->limit,
                'sort' => $query->sort,
            ],
            pagination: [
                'cursor' => $query->cursor !== '' ? $query->cursor : null,
                'posts' => [
                    'nextCursor' => $postsNextCursor,
                    'hasMore' => $postsNextCursor !== null,
                ],
                'actors' => [
                    'nextCursor' => $actorsNextCursor,
                    'hasMore' => $actorsNextCursor !== null,
                ],
            ],
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    private function matchesPostFilters(array $item, BlueskySearchQueryDTO $query): bool
    {
        if (! $this->matchesAuthorFilter($item, $query->author)) {
            return false;
        }

        if (! $this->matchesLanguageFilter($item, $query->language)) {
            return false;
        }

        if (! $this->matchesMentionsFilter($item, $query->mentions)) {
            return false;
        }

        if (! $this->matchesDomainFilter($item, $query->domain)) {
            return false;
        }

        if (! $this->matchesUrlFilter($item, $query->url)) {
            return false;
        }

        if (! $this->matchesTagFilter($item, $query->tag)) {
            return false;
        }

        return $this->matchesDateRangeFilter($item, $query->since, $query->until);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function matchesAuthorFilter(array $item, string $author): bool
    {
        $needle = strtolower(trim($author));

        if ($needle === '') {
            return true;
        }

        return collect([
            (string) data_get($item, 'author.handle', ''),
            (string) data_get($item, 'author.displayName', ''),
            (string) data_get($item, 'author.did', ''),
        ])
            ->map(fn (string $value): string => strtolower($value))
            ->contains(fn (string $value): bool => str_contains($value, $needle));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function matchesLanguageFilter(array $item, string $language): bool
    {
        if ($language === '') {
            return true;
        }

        return collect($item['languages'] ?? [])
            ->map(fn (mixed $value): string => strtolower((string) $value))
            ->contains($language);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function matchesMentionsFilter(array $item, string $mentions): bool
    {
        if ($mentions === '') {
            return true;
        }

        return collect($item['mentions'] ?? [])
            ->map(fn (mixed $value): string => ltrim(strtolower((string) $value), '@'))
            ->contains(fn (string $value): bool => str_contains($value, $mentions));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function matchesDomainFilter(array $item, string $domain): bool
    {
        if ($domain === '') {
            return true;
        }

        return collect($item['domains'] ?? [])
            ->map(fn (mixed $value): string => strtolower((string) $value))
            ->contains(fn (string $value): bool => $value === $domain);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function matchesUrlFilter(array $item, string $url): bool
    {
        if ($url === '') {
            return true;
        }

        return collect($item['links'] ?? [])
            ->map(fn (mixed $value): string => (string) $value)
            ->contains(fn (string $value): bool => str_contains($value, $url));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function matchesTagFilter(array $item, string $tag): bool
    {
        if ($tag === '') {
            return true;
        }

        return collect($item['hashtags'] ?? [])
            ->map(fn (mixed $value): string => ltrim(strtolower((string) $value), '#'))
            ->contains(fn (string $value): bool => $value === $tag);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function matchesDateRangeFilter(array $item, string $since, string $until): bool
    {
        if ($since === '' && $until === '') {
            return true;
        }

        $createdAt = $this->toTimestamp((string) ($item['createdAt'] ?? ''));

        if ($createdAt === null) {
            return false;
        }

        $sinceTimestamp = $this->toTimestamp($since);

        if ($sinceTimestamp !== null && $createdAt < $sinceTimestamp) {
            return false;
        }

        $untilTimestamp = $this->toTimestamp($until);

        if ($untilTimestamp !== null && $createdAt > $untilTimestamp) {
            return false;
        }

        return true;
    }

    private function toTimestamp(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : $timestamp;
    }

    private function normalizedCursor(mixed $value): ?string
    {
        $cursor = trim((string) $value);

        return $cursor !== '' ? $cursor : null;
    }
}
