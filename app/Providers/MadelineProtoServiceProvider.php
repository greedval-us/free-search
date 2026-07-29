<?php

namespace App\Providers;

use App\Support\MadelineProto\MadelineProtoConfig;
use App\Support\MadelineProto\MadelineProtoClientFactory;
use App\Support\MadelineProto\MadelineProtoManager;
use App\Support\MadelineProto\MadelineProtoSessionPool;
use Illuminate\Support\ServiceProvider;

class MadelineProtoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MadelineProtoConfig::class,
            static fn (): MadelineProtoConfig => MadelineProtoConfig::fromArray(
                (array) config('madelineproto', [])
            )
        );

        $this->app->singleton(MadelineProtoClientFactory::class);
        $this->app->singleton(MadelineProtoSessionPool::class);
        $this->app->singleton(MadelineProtoManager::class);
    }

    public function boot(): void
    {
        //
    }
}
