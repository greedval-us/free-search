<?php

namespace App\Providers;

use App\Modules\Dorks\Application\Services\DorkAnalyticsBuilder;
use App\Modules\Dorks\Application\Services\DorkResultQualityFilter;
use App\Modules\Dorks\Application\Services\DorkSearchService;
use App\Modules\Dorks\Infrastructure\Providers\DorkBingRssProvider;
use App\Modules\Dorks\Infrastructure\Providers\DorkBraveSearchProvider;
use App\Modules\Dorks\Infrastructure\Providers\DorkDuckDuckGoProvider;
use App\Modules\Dorks\Infrastructure\Providers\DorkRedditRssProvider;
use App\Modules\Dorks\Infrastructure\Providers\DorkYahooSearchProvider;
use App\Modules\Dorks\Infrastructure\Providers\DorkYandexSearchProvider;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\TelegramAnalyticsApplicationService;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Parser\TelegramParserApplicationService;
use App\Modules\Telegram\Search\Contracts\TelegramSearchApplicationServiceInterface;
use App\Modules\Telegram\Search\TelegramSearchApplicationService;
use App\Modules\Telegram\TelegramService;
use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Infrastructure\Providers\FioMultiSourceSearchProvider;
use App\Modules\Username\Domain\Contracts\UsernameSourceCheckerInterface;
use App\Modules\Username\Infrastructure\Http\UsernameSourceHttpChecker;
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
        $this->app->bind(DorkSearchService::class, function ($app): DorkSearchService {
            return new DorkSearchService(
                providers: [
                    'bing' => $app->make(DorkBingRssProvider::class),
                    'duckduckgo' => $app->make(DorkDuckDuckGoProvider::class),
                    'yahoo' => $app->make(DorkYahooSearchProvider::class),
                    'brave' => $app->make(DorkBraveSearchProvider::class),
                    'yandex' => $app->make(DorkYandexSearchProvider::class),
                    'reddit' => $app->make(DorkRedditRssProvider::class),
                ],
                analyticsBuilder: $app->make(DorkAnalyticsBuilder::class),
                qualityFilter: $app->make(DorkResultQualityFilter::class),
            );
        });

        $this->app->bind(TelegramGatewayInterface::class, TelegramService::class);
        $this->app->bind(TelegramSearchApplicationServiceInterface::class, TelegramSearchApplicationService::class);
        $this->app->bind(TelegramParserApplicationServiceInterface::class, TelegramParserApplicationService::class);
        $this->app->bind(TelegramAnalyticsApplicationServiceInterface::class, TelegramAnalyticsApplicationService::class);
        $this->app->bind(UsernameSourceCheckerInterface::class, UsernameSourceHttpChecker::class);
        $this->app->bind(FioPublicSearchProviderInterface::class, FioMultiSourceSearchProvider::class);
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
