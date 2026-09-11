<?php

namespace App\Modules\Telegram\Providers;

use App\Modules\ParserSupport\Contracts\ParserRunBackgroundProcessorInterface;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsRangeResolverInterface;
use App\Modules\Telegram\Analytics\TelegramAnalyticsApplicationService;
use App\Modules\Telegram\Analytics\TelegramAnalyticsRangeResolver;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserExportBuilderInterface;
use App\Modules\Telegram\Parser\TelegramParserApplicationService;
use App\Modules\Telegram\Parser\TelegramParserExportBuilder;
use App\Modules\Telegram\Search\Contracts\TelegramSearchApplicationServiceInterface;
use App\Modules\Telegram\Search\TelegramSearchApplicationService;
use App\Modules\Telegram\Support\TelegramConfig;
use App\Modules\Telegram\Support\TelegramConfigFactory;
use App\Modules\Telegram\TelegramService;
use App\Modules\Telegram\Tracking\Console\MaintainTracking;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\Telegram\Tracking\MadelineTrackingGateway;
use App\Support\Providers\BindingsServiceProvider;
use Illuminate\Support\Facades\Schedule;

final class TelegramServiceProvider extends BindingsServiceProvider
{
    public function boot(): void
    {
        $this->commands([MaintainTracking::class]);
        Schedule::command('telegram:tracking-maintain')->everyMinute()->withoutOverlapping();
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(
            TelegramConfig::class,
            fn (): TelegramConfig => $this->app->make(TelegramConfigFactory::class)->make(
                (array) config('osint.telegram', []),
                (string) config('app.timezone', 'UTC')
            )
        );

        $this->app->tag(
            [TelegramParserApplicationService::class],
            ParserRunBackgroundProcessorInterface::CONTAINER_TAG,
        );
    }

    protected function bindings(): array
    {
        return [
            TrackingGateway::class => MadelineTrackingGateway::class,
            TelegramGatewayInterface::class => TelegramService::class,
            TelegramSearchApplicationServiceInterface::class => TelegramSearchApplicationService::class,
            TelegramParserApplicationServiceInterface::class => TelegramParserApplicationService::class,
            TelegramParserExportBuilderInterface::class => TelegramParserExportBuilder::class,
            TelegramAnalyticsApplicationServiceInterface::class => TelegramAnalyticsApplicationService::class,
            TelegramAnalyticsRangeResolverInterface::class => TelegramAnalyticsRangeResolver::class,
        ];
    }
}
