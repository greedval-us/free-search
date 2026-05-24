<?php

namespace App\Providers;

use App\Support\Providers\BindingsServiceProvider;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use App\Support\Reports\ReportsConfig;
use App\Support\Reports\ReportFilenamePolicy;

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
            ReportFilenamePolicyInterface::class => ReportFilenamePolicy::class,
        ];
    }
}
