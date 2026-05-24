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
        $providers = [
            'newsapi' => fn (): array => $this->newsApiProvider->fetch($query),
            'googlenews' => fn (): array => $this->googleNewsRssProvider->fetch($query),
            'bing' => fn (): array => $this->bingNewsRssProvider->fetch($query),
        ];
        $result = [];

        foreach ($this->config->providerOrder() as $providerKey) {
            $loader = $providers[$providerKey] ?? null;
            if (!is_callable($loader)) {
                continue;
            }

            $result = [
                ...$result,
                ...array_slice($loader(), 0, $perProviderLimit),
            ];
        }

        return $result;
    }
}
