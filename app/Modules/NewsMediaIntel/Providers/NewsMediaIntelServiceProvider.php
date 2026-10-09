<?php

namespace App\Modules\NewsMediaIntel\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsMediaIntelServiceInterface;
use App\Modules\NewsMediaIntel\Application\Contracts\SearxngSearchClientInterface;
use App\Modules\NewsMediaIntel\Application\Reports\Console\MaintainNewsMediaReports;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntelService;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\SearxngNewsFeedFetcher;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\SearxngSearchClient;
use App\Support\Providers\BindingsServiceProvider;
use Illuminate\Support\Facades\Schedule;

final class NewsMediaIntelServiceProvider extends BindingsServiceProvider
{
    public function boot(): void
    {
        $this->commands([MaintainNewsMediaReports::class]);
        Schedule::command('news-media:reports-maintain')->everyMinute()->withoutOverlapping();
    }

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
            NewsFeedFetcherInterface::class => SearxngNewsFeedFetcher::class,
            NewsMediaIntelServiceInterface::class => NewsMediaIntelService::class,
            SearxngSearchClientInterface::class => SearxngSearchClient::class,
        ];
    }
}
