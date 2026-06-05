<?php

namespace App\Modules\Mastodon\Analytics;

use App\Modules\Mastodon\Analytics\Contracts\MastodonAnalyticsApplicationServiceInterface;
use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\DTO\Request\MastodonAnalyticsQueryDTO;
use App\Modules\Mastodon\DTO\Result\MastodonAnalyticsResultDTO;
use App\Modules\Mastodon\Presenters\MastodonAccountPresenter;
use App\Modules\Mastodon\Presenters\MastodonHashtagPresenter;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;
use RuntimeException;

final class MastodonAnalyticsApplicationService implements MastodonAnalyticsApplicationServiceInterface
{
    private const SEARCH_PICK_LIMIT = 10;
    public function __construct(
        private readonly MastodonGatewayInterface $gateway,
        private readonly MastodonStatusPresenter $statusPresenter,
        private readonly MastodonAccountPresenter $accountPresenter,
        private readonly MastodonHashtagPresenter $hashtagPresenter,
        private readonly MastodonAnalyticsReportBuilder $reportBuilder,
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

        return $this->reportBuilder->build(
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

        return $this->reportBuilder->build(
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

}
