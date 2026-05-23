<?php

namespace App\Modules\CompanyIntel\Providers;

use App\Modules\CompanyIntel\Application\Contracts\CompanyIntelServiceInterface;
use App\Modules\CompanyIntel\Application\Services\CompanyIntelService;
use App\Support\Providers\BindingsServiceProvider;

final class CompanyIntelServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            CompanyIntelServiceInterface::class => CompanyIntelService::class,
        ];
    }
}

