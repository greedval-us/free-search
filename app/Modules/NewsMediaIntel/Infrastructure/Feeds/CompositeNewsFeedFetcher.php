<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;

final class CompositeNewsFeedFetcher implements NewsFeedFetcherInterface
{
    public function __construct(
        private readonly GoogleNewsRssProvider $googleNewsRssProvider,
        private readonly BingNewsRssProvider $bingNewsRssProvider,
        private readonly NewsApiProvider $newsApiProvider,
        private readonly NewsMediaIntelConfig $config,
    ) {
    }

    public function fetchAll(string $query): array
    {
        $perProviderLimit = $this->config->perProviderLimit();

        return [
            ...array_slice($this->newsApiProvider->fetch($query), 0, $perProviderLimit),
            ...array_slice($this->googleNewsRssProvider->fetch($query), 0, $perProviderLimit),
            ...array_slice($this->bingNewsRssProvider->fetch($query), 0, $perProviderLimit),
        ];
    }
}
