<?php

namespace App\Providers;

use App\Support\Providers\BindingsServiceProvider;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use App\Support\Reports\ReportFilenamePolicy;

final class SupportServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            ReportFilenamePolicyInterface::class => ReportFilenamePolicy::class,
        ];
    }
}

