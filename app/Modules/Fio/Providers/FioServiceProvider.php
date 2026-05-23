<?php

namespace App\Modules\Fio\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Infrastructure\Providers\FioMultiSourceSearchProvider;
use App\Support\Providers\BindingsServiceProvider;

final class FioServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            FioPublicSearchProviderInterface::class => FioMultiSourceSearchProvider::class,
        ];
    }
}
