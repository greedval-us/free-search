<?php

namespace App\Modules\CompanyIntel\Providers;

use App\Modules\CompanyIntel\Application\Contracts\CompanyIntelServiceInterface;
use App\Modules\CompanyIntel\Application\Services\CompanyIntelService;
use App\Modules\CompanyIntel\Application\Support\CompanyIntelConfig;
use App\Support\Providers\BindingsServiceProvider;

final class CompanyIntelServiceProvider extends BindingsServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(
            CompanyIntelConfig::class,
            static fn (): CompanyIntelConfig => CompanyIntelConfig::fromArray(
                (array) config('osint.company_intel', [])
            )
        );
    }

    protected function bindings(): array
    {
        return [
            CompanyIntelServiceInterface::class => CompanyIntelService::class,
        ];
    }
}
