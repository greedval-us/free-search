<?php

namespace App\Modules\NewsMediaIntel\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsMediaIntelServiceInterface;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntelService;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\CompositeNewsFeedFetcher;
use App\Support\Providers\BindingsServiceProvider;

final class NewsMediaIntelServiceProvider extends BindingsServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(NewsMediaIntelConfig::class, function (): NewsMediaIntelConfig {
            return NewsMediaIntelConfig::fromArray(
                (array) config('osint.news_media_intel', [])
            );
        });
    }

    protected function bindings(): array
    {
        return [
            NewsFeedFetcherInterface::class => CompositeNewsFeedFetcher::class,
            NewsMediaIntelServiceInterface::class => NewsMediaIntelService::class,
        ];
    }
}
