<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;

final class RssNewsFeedFetcher implements NewsFeedFetcherInterface
{
    public function __construct(
        private readonly GoogleNewsRssProvider $googleNewsRssProvider,
        private readonly BingNewsRssProvider $bingNewsRssProvider,
        private readonly NewsApiProvider $newsApiProvider,
    ) {
    }

    public function fetchAll(string $query): array
    {
        $perProviderLimit = 40;

        return [
            ...array_slice($this->newsApiProvider->fetch($query), 0, $perProviderLimit),
            ...array_slice($this->googleNewsRssProvider->fetch($query), 0, $perProviderLimit),
            ...array_slice($this->bingNewsRssProvider->fetch($query), 0, $perProviderLimit),
        ];
    }
}
