<?php

namespace App\Modules\NewsMediaIntel\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsMediaIntelServiceInterface;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntelService;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Application\Support\NewsFeedProviderRegistry;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\BingNewsRssProvider;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\CompositeNewsFeedFetcher;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\GoogleNewsRssProvider;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\NewsApiProvider;
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

        $this->app->singleton(NewsFeedProviderRegistry::class, function (): NewsFeedProviderRegistry {
            return new NewsFeedProviderRegistry([
                $this->app->make(NewsApiProvider::class),
                $this->app->make(GoogleNewsRssProvider::class),
                $this->app->make(BingNewsRssProvider::class),
            ]);
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
