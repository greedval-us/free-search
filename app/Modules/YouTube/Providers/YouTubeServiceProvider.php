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
use App\Support\Providers\BindingsServiceProvider;

final class YouTubeServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            YouTubeGatewayInterface::class => YouTubeDataApiClient::class,
            YouTubeSearchApplicationServiceInterface::class => YouTubeSearchApplicationService::class,
            YouTubeParserApplicationServiceInterface::class => YouTubeParserApplicationService::class,
            YouTubeAnalyticsApplicationServiceInterface::class => YouTubeAnalyticsApplicationService::class,
        ];
    }
}
