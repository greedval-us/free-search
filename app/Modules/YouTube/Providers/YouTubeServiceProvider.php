<?php

namespace App\Modules\YouTube\Providers;

use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use App\Modules\YouTube\Analytics\YouTubeAnalyticsApplicationService;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserExportBuilderInterface;
use App\Modules\YouTube\Parser\YouTubeParserApplicationService;
use App\Modules\YouTube\Parser\YouTubeParserExportBuilder;
use App\Modules\YouTube\Search\Contracts\YouTubeSearchApplicationServiceInterface;
use App\Modules\YouTube\Search\YouTubeSearchApplicationService;
use App\Modules\YouTube\Support\YouTubeApiConfig;
use App\Modules\YouTube\YouTubeDataApiClient;
use App\Support\Providers\BindingsServiceProvider;

final class YouTubeServiceProvider extends BindingsServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(YouTubeApiConfig::class, function (): YouTubeApiConfig {
            return YouTubeApiConfig::fromArray(
                (array) config('services.youtube', [])
            );
        });
    }

    protected function bindings(): array
    {
        return [
            YouTubeGatewayInterface::class => YouTubeDataApiClient::class,
            YouTubeSearchApplicationServiceInterface::class => YouTubeSearchApplicationService::class,
            YouTubeParserApplicationServiceInterface::class => YouTubeParserApplicationService::class,
            YouTubeParserExportBuilderInterface::class => YouTubeParserExportBuilder::class,
            YouTubeAnalyticsApplicationServiceInterface::class => YouTubeAnalyticsApplicationService::class,
        ];
    }
}
