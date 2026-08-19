<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Support\AdminNavigationCatalog;
use App\Support\Observability\MoonShineSecurityConfig;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

class MoonShineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MoonShineSecurityConfig::class,
            static fn (): MoonShineSecurityConfig => MoonShineSecurityConfig::fromArray(
                (array) config('moonshine.security', []),
            ),
        );
    }

    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core
            ->resources(AdminNavigationCatalog::resources())
            ->pages([
                ...$core->getConfig()->getPages(),
            ]);
    }
}
