<?php

namespace App\Modules\Export\Providers;

use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\Export\Excel\ExcelWorkbookService;
use App\Support\Providers\BindingsServiceProvider;

final class ExportServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            ExcelWorkbookServiceInterface::class => ExcelWorkbookService::class,
        ];
    }
}

