<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Support\AdminAccess;
use App\MoonShine\Support\AdminDashboardConfig;
use App\MoonShine\Support\AdminNavigationCatalog;
use App\Support\Observability\MoonShineSecurityConfig;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Support\Enums\Ability;

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

        $this->app->singleton(AdminAccess::class);
        $this->app->singleton(
            AdminDashboardConfig::class,
            static fn (): AdminDashboardConfig => AdminDashboardConfig::fromArray(
                (array) config('admin_panel.dashboard', []),
            ),
        );
    }

    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        RateLimiter::for('telegram-session-auth', static fn (Request $request): Limit => Limit::perMinute(
            (int) config('madelineproto.admin_auth.requests_per_minute'),
        )->by('telegram-session:'.(auth('moonshine')->id() ?? $request->ip())));
        $this->loadRoutesFrom(base_path('routes/moonshine.php'));

        $core->getConfig()->authorizationRules(
            static fn (
                ResourceContract $resource,
                mixed $user,
                Ability $ability,
                mixed $data,
            ): bool => app(AdminAccess::class)->allows(
                $user instanceof MoonshineUser ? $user : null,
                $resource::class,
                $ability,
            ),
        );

        $core
            ->resources(AdminNavigationCatalog::resources())
            ->pages([
                ...$core->getConfig()->getPages(),
                ...AdminNavigationCatalog::pages(),
            ]);
    }
}
