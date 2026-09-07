<?php

namespace App\Modules\ParserSupport\Providers;

use App\Modules\ParserSupport\Contracts\ParserRunBackgroundProcessorInterface;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunBackgroundProcessorRegistry;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunJobDispatcher;
use App\Modules\ParserSupport\ParserRunQueueConfigurationGuard;
use Illuminate\Support\ServiceProvider;

final class ParserSupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ParserRunConfig::class,
            static fn (): ParserRunConfig => ParserRunConfig::fromArray(
                (array) config('osint.parser_runs', []),
            ),
        );

        $this->app->singleton(
            ParserRunJobDispatcherInterface::class,
            ParserRunJobDispatcher::class,
        );

        $this->app->singleton(
            ParserRunBackgroundProcessorRegistry::class,
            fn (): ParserRunBackgroundProcessorRegistry => new ParserRunBackgroundProcessorRegistry(
                $this->app->tagged(ParserRunBackgroundProcessorInterface::CONTAINER_TAG),
            ),
        );
    }

    public function boot(ParserRunQueueConfigurationGuard $configurationGuard): void
    {
        $configurationGuard->ensureSafe();
    }
}
