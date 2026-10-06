<?php

namespace App\Providers;

use App\Console\Commands\MaintainMonitoring;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\SearxngNewsFeedFetcher;
use App\Modules\NewsMediaIntel\Monitoring\NewsPageFetcher;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

final class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NewsPageFetcher::class, SearxngNewsFeedFetcher::class);
    }

    public function boot(): void
    {
        $this->commands([MaintainMonitoring::class]);
        Schedule::command('monitoring:maintain')->everyMinute()->withoutOverlapping();
        Schedule::command('monitoring:maintain --prune')->dailyAt('04:30')->withoutOverlapping();
    }
}
