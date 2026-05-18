<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;

final class RssNewsFeedFetcher implements NewsFeedFetcherInterface
{
    public function __construct(
        private readonly GoogleNewsRssProvider $googleNewsRssProvider,
        private readonly BingNewsRssProvider $bingNewsRssProvider,
    ) {
    }

    public function fetchAll(string $query): array
    {
        return [
            ...$this->googleNewsRssProvider->fetch($query),
            ...$this->bingNewsRssProvider->fetch($query),
        ];
    }
}

