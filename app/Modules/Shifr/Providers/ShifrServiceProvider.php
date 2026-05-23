<?php

namespace App\Modules\Shifr\Providers;

use App\Modules\Shifr\Application\Contracts\ShifrClassicCipherServiceInterface;
use App\Modules\Shifr\Application\Contracts\ShifrToolkitServiceInterface;
use App\Modules\Shifr\Application\Services\ShifrClassicCipherService;
use App\Modules\Shifr\Application\Services\ShifrToolkitService;
use Illuminate\Support\ServiceProvider;

final class ShifrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShifrClassicCipherServiceInterface::class, ShifrClassicCipherService::class);
        $this->app->bind(ShifrToolkitServiceInterface::class, ShifrToolkitService::class);
    }
}

