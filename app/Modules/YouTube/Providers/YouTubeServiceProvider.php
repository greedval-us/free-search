<?php

namespace App\Modules\YouTube\Providers;

use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use App\Modules\YouTube\Analytics\YouTubeAnalyticsApplicationService;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Modules\YouTube\Parser\YouTubeParserApplicationService;
use App\Modules\YouTube\Search\Contracts\YouTubeSearchApplicationServiceInterface;
use App\Modules\YouTube\Search\YouTubeSearchApplicationService;
use App\Modules\YouTube\YouTubeDataApiClient;
use Illuminate\Support\ServiceProvider;

final class YouTubeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(YouTubeGatewayInterface::class, YouTubeDataApiClient::class);
        $this->app->bind(YouTubeSearchApplicationServiceInterface::class, YouTubeSearchApplicationService::class);
        $this->app->bind(YouTubeParserApplicationServiceInterface::class, YouTubeParserApplicationService::class);
        $this->app->bind(YouTubeAnalyticsApplicationServiceInterface::class, YouTubeAnalyticsApplicationService::class);
    }
}

