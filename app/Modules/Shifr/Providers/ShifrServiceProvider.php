<?php

namespace App\Modules\Shifr\Providers;

use App\Modules\Shifr\Application\Contracts\ShifrClassicCipherServiceInterface;
use App\Modules\Shifr\Application\Contracts\ShifrToolkitServiceInterface;
use App\Modules\Shifr\Application\Services\ShifrClassicCipherService;
use App\Modules\Shifr\Application\Services\ShifrToolkitService;
use App\Support\Providers\BindingsServiceProvider;

final class ShifrServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            ShifrClassicCipherServiceInterface::class => ShifrClassicCipherService::class,
            ShifrToolkitServiceInterface::class => ShifrToolkitService::class,
        ];
    }
}
