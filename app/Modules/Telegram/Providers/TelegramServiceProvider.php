<?php

namespace App\Modules\Telegram\Providers;

use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\TelegramAnalyticsApplicationService;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Parser\TelegramParserApplicationService;
use App\Modules\Telegram\Search\Contracts\TelegramSearchApplicationServiceInterface;
use App\Modules\Telegram\Search\TelegramSearchApplicationService;
use App\Modules\Telegram\TelegramService;
use Illuminate\Support\ServiceProvider;

final class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TelegramGatewayInterface::class, TelegramService::class);
        $this->app->bind(TelegramSearchApplicationServiceInterface::class, TelegramSearchApplicationService::class);
        $this->app->bind(TelegramParserApplicationServiceInterface::class, TelegramParserApplicationService::class);
        $this->app->bind(TelegramAnalyticsApplicationServiceInterface::class, TelegramAnalyticsApplicationService::class);
    }
}

