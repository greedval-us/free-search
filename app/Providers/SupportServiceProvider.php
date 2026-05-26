<?php

namespace App\Providers;

use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use App\Services\Access\FeatureAccessService;
use App\Support\Providers\BindingsServiceProvider;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use App\Support\Reports\ReportFilenamePolicy;
use App\Support\Reports\ReportsConfig;

final class SupportServiceProvider extends BindingsServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(
            ReportsConfig::class,
            static fn (): ReportsConfig => ReportsConfig::fromArray(
                (array) config('osint.reports', []),
                (string) config('app.timezone', 'UTC')
            )
        );
    }

    protected function bindings(): array
    {
        return [
            FeatureAccessServiceInterface::class => FeatureAccessService::class,
            ReportFilenamePolicyInterface::class => ReportFilenamePolicy::class,
        ];
    }
}
