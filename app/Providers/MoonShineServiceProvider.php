<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\AdminAuditLog\AdminAuditLogResource;
use App\MoonShine\Resources\FailedJob\FailedJobResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\QueueJob\QueueJobResource;
use App\MoonShine\Resources\RequestLog\RequestLogResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                AppUserResource::class,
                RequestLogResource::class,
                QueueJobResource::class,
                FailedJobResource::class,
                AdminAuditLogResource::class,
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
            ]);
    }
}
