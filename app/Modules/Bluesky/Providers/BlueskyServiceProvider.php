<?php

namespace App\Modules\Bluesky\Providers;

use App\Modules\Bluesky\Analytics\BlueskyAnalyticsApplicationService;
use App\Modules\Bluesky\Analytics\Contracts\BlueskyAnalyticsApplicationServiceInterface;
use App\Modules\Bluesky\BlueskyApiClient;
use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use App\Modules\Bluesky\Parser\BlueskyParserApplicationService;
use App\Modules\Bluesky\Parser\BlueskyParserExportBuilder;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserApplicationServiceInterface;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserExportBuilderInterface;
use App\Modules\Bluesky\Search\BlueskySearchApplicationService;
use App\Modules\Bluesky\Search\Contracts\BlueskySearchApplicationServiceInterface;
use App\Modules\Bluesky\Support\BlueskyApiConfig;
use App\Modules\Bluesky\Support\BlueskyActorResolver;
use App\Modules\Bluesky\Support\BlueskyModuleConfig;
use App\Support\Providers\BindingsServiceProvider;

final class BlueskyServiceProvider extends BindingsServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(
            BlueskyApiConfig::class,
            static fn (): BlueskyApiConfig => BlueskyApiConfig::fromArray(
                (array) config('services.bluesky', [])
            )
        );

        $this->app->singleton(
            BlueskyModuleConfig::class,
            static fn (): BlueskyModuleConfig => BlueskyModuleConfig::fromArray(
                (array) config('osint.bluesky', [])
            )
        );

        $this->app->singleton(BlueskyActorResolver::class);
    }

    protected function bindings(): array
    {
        return [
            BlueskyGatewayInterface::class => BlueskyApiClient::class,
            BlueskyAnalyticsApplicationServiceInterface::class => BlueskyAnalyticsApplicationService::class,
            BlueskyParserApplicationServiceInterface::class => BlueskyParserApplicationService::class,
            BlueskyParserExportBuilderInterface::class => BlueskyParserExportBuilder::class,
            BlueskySearchApplicationServiceInterface::class => BlueskySearchApplicationService::class,
        ];
    }
}
