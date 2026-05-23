<?php

namespace App\Modules\NewsMediaIntel\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Infrastructure\Providers\RssNewsFeedFetcher;
use Illuminate\Support\ServiceProvider;

final class NewsMediaIntelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NewsFeedFetcherInterface::class, RssNewsFeedFetcher::class);
    }
}

