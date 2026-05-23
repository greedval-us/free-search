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
use App\Support\Providers\BindingsServiceProvider;

final class TelegramServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            TelegramGatewayInterface::class => TelegramService::class,
            TelegramSearchApplicationServiceInterface::class => TelegramSearchApplicationService::class,
            TelegramParserApplicationServiceInterface::class => TelegramParserApplicationService::class,
            TelegramAnalyticsApplicationServiceInterface::class => TelegramAnalyticsApplicationService::class,
        ];
    }
}
