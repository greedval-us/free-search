<?php

namespace App\Modules\Telegram\Providers;

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
use App\Modules\Telegram\Support\Contracts\TelegramMediaResponderInterface;
use App\Modules\Telegram\Support\TelegramMediaResponder;
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
            TelegramParserExportBuilderInterface::class => TelegramParserExportBuilder::class,
            TelegramAnalyticsApplicationServiceInterface::class => TelegramAnalyticsApplicationService::class,
            TelegramAnalyticsRangeResolverInterface::class => TelegramAnalyticsRangeResolver::class,
            TelegramMediaResponderInterface::class => TelegramMediaResponder::class,
        ];
    }
}
