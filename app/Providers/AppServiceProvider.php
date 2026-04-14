<?php

namespace App\Providers;

use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\TelegramAnalyticsApplicationService;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Parser\TelegramParserApplicationService;
use App\Modules\Telegram\Search\Contracts\TelegramSearchApplicationServiceInterface;
use App\Modules\Telegram\Search\TelegramSearchApplicationService;
use App\Modules\Telegram\TelegramService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TelegramGatewayInterface::class, TelegramService::class);
        $this->app->bind(TelegramSearchApplicationServiceInterface::class, TelegramSearchApplicationService::class);
        $this->app->bind(TelegramParserApplicationServiceInterface::class, TelegramParserApplicationService::class);
        $this->app->bind(TelegramAnalyticsApplicationServiceInterface::class, TelegramAnalyticsApplicationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

