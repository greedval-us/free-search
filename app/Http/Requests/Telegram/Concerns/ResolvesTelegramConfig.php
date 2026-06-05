<?php

namespace App\Http\Requests\Telegram\Concerns;

use App\Modules\Telegram\Support\TelegramConfig;

trait ResolvesTelegramConfig
{
    protected function telegramConfig(): TelegramConfig
    {
        return app(TelegramConfig::class);
    }
}
