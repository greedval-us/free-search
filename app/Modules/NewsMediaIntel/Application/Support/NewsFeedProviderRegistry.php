<?php

namespace App\Modules\NewsMediaIntel\Application\Support;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;

final class NewsFeedProviderRegistry
{
    /**
     * @var array<string, NewsFeedProviderInterface>
     */
    private array $providers = [];

    /**
     * @param iterable<NewsFeedProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $key = strtolower(trim($provider->key()));
            if ($key === '') {
                continue;
            }

            $this->providers[$key] = $provider;
        }
    }

    public function get(string $key): ?NewsFeedProviderInterface
    {
        $normalized = strtolower(trim($key));
        if ($normalized === '') {
            return null;
        }

        return $this->providers[$normalized] ?? null;
    }
}

