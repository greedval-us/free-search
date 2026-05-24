<?php

namespace App\Http\Requests\Telegram\Concerns;

use App\Modules\Telegram\Support\TelegramConfig;

trait ResolvesTelegramConfig
{
    private function telegramConfig(): TelegramConfig
    {
        /** @var TelegramConfig|null $config */
        static $config = null;

        return $config ??= TelegramConfig::fromArray(
            (array) config('osint.telegram', []),
            (string) config('app.timezone', 'UTC')
        );
    }
}
