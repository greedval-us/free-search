<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Support\NewsFeedProviderRegistry;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;

final class CompositeNewsFeedFetcher implements NewsFeedFetcherInterface
{
    public function __construct(
        private readonly NewsFeedProviderRegistry $providerRegistry,
        private readonly NewsMediaIntelConfig $config,
    ) {}

    public function fetchAll(string $query): array
    {
        $perProviderLimit = $this->config->perProviderLimit();
        $result = [];

        foreach ($this->config->providerOrder() as $providerKey) {
            $provider = $this->providerRegistry->get($providerKey);
            if ($provider === null) {
                continue;
            }

            $result = [
                ...$result,
                ...array_slice($provider->fetch($query), 0, $perProviderLimit),
            ];
        }

        return $result;
    }
}
