<?php

namespace App\Modules\NewsMediaIntel\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsMediaIntelServiceInterface;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntelService;
use App\Modules\NewsMediaIntel\Infrastructure\Providers\RssNewsFeedFetcher;
use App\Support\Providers\BindingsServiceProvider;

final class NewsMediaIntelServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            NewsFeedFetcherInterface::class => RssNewsFeedFetcher::class,
            NewsMediaIntelServiceInterface::class => NewsMediaIntelService::class,
        ];
    }
}
