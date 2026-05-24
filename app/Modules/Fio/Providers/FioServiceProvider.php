<?php

namespace App\Modules\Fio\Providers;

use App\Modules\Fio\Application\Contracts\FioPublicSearchServiceInterface;
use App\Modules\Fio\Application\Services\FioPublicSearchService;
use App\Modules\Fio\Application\Support\FioHttpConfig;
use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Infrastructure\Providers\FioMultiSourceSearchProvider;
use App\Support\Providers\BindingsServiceProvider;

final class FioServiceProvider extends BindingsServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(
            FioHttpConfig::class,
            static fn (): FioHttpConfig => FioHttpConfig::fromArray((array) config('osint.fio.http', []))
        );
    }

    protected function bindings(): array
    {
        return [
            FioPublicSearchProviderInterface::class => FioMultiSourceSearchProvider::class,
            FioPublicSearchServiceInterface::class => FioPublicSearchService::class,
        ];
    }
}
