<?php

namespace App\Modules\Fio\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Infrastructure\Providers\FioMultiSourceSearchProvider;
use Illuminate\Support\ServiceProvider;

final class FioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FioPublicSearchProviderInterface::class, FioMultiSourceSearchProvider::class);
    }
}

