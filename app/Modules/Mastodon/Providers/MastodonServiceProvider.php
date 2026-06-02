<?php

namespace App\Modules\Mastodon\Providers;

use App\Modules\Mastodon\Analytics\Contracts\MastodonAnalyticsApplicationServiceInterface;
use App\Modules\Mastodon\Analytics\MastodonAnalyticsApplicationService;
use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\MastodonApiClient;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserApplicationServiceInterface;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserExportBuilderInterface;
use App\Modules\Mastodon\Parser\MastodonParserApplicationService;
use App\Modules\Mastodon\Parser\MastodonParserExportBuilder;
use App\Modules\Mastodon\Search\Contracts\MastodonSearchApplicationServiceInterface;
use App\Modules\Mastodon\Search\MastodonSearchApplicationService;
use App\Modules\Mastodon\Support\MastodonApiConfig;
use App\Modules\Mastodon\Support\MastodonModuleConfig;
use App\Support\Providers\BindingsServiceProvider;

final class MastodonServiceProvider extends BindingsServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(
            MastodonApiConfig::class,
            static fn (): MastodonApiConfig => MastodonApiConfig::fromArray(
                (array) config('services.mastodon', [])
            )
        );

        $this->app->singleton(
            MastodonModuleConfig::class,
            static fn (): MastodonModuleConfig => MastodonModuleConfig::fromArray(
                (array) config('osint.mastodon', [])
            )
        );
    }

    protected function bindings(): array
    {
        return [
            MastodonGatewayInterface::class => MastodonApiClient::class,
            MastodonAnalyticsApplicationServiceInterface::class => MastodonAnalyticsApplicationService::class,
            MastodonParserApplicationServiceInterface::class => MastodonParserApplicationService::class,
            MastodonParserExportBuilderInterface::class => MastodonParserExportBuilder::class,
            MastodonSearchApplicationServiceInterface::class => MastodonSearchApplicationService::class,
        ];
    }
}
