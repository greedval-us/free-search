<?php

namespace App\Modules\TelegramBot\Application;

use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;

final readonly class BotAccess
{
    public function __construct(private BotConfig $config) {}

    public function allows(?BotLink $link): bool
    {
        if (! $this->config->active() || $link === null) {
            return false;
        }
        $user = $link->user;
        $chat = $link->chat;

        return $user !== null && ! $user->isBlocked() && $user->hasVerifiedEmail()
            && $user->telegram_id === $link->telegram_id
            && $chat !== null && (string) $chat->chat_id === $link->telegram_id
            && (int) $chat->telegraph_bot_id === $this->config->botId();
    }
}
