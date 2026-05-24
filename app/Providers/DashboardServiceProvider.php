<?php

namespace App\Providers;

use App\Services\Dashboard\Contracts\ModulePinServiceInterface;
use App\Services\Dashboard\Contracts\SavedQueryServiceInterface;
use App\Services\Dashboard\Contracts\UserDashboardServiceInterface;
use App\Services\Dashboard\ModulePinService;
use App\Services\Dashboard\SavedQueryService;
use App\Services\Dashboard\UserDashboardService;
use App\Support\Providers\BindingsServiceProvider;

final class DashboardServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            UserDashboardServiceInterface::class => UserDashboardService::class,
            SavedQueryServiceInterface::class => SavedQueryService::class,
            ModulePinServiceInterface::class => ModulePinService::class,
        ];
    }
}

