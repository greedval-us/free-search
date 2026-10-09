<?php

namespace App\Modules\TelegramBot\Application;

use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;

final readonly class BotAccess
{
    public function __construct(private BotConfig $config, private ScheduledReportRegistry $reports) {}

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

    public function allowsDelivery(?BotLink $link, string $kind, bool $automatic, ?int $reportId = null): bool
    {
        if (! $this->allows($link)) {
            return false;
        }

        $provider = $this->reports->find($kind);
        if ($provider !== null) {
            if ($automatic && ! $link->exports_enabled) {
                return false;
            }

            return $reportId === null ? $provider->allows($link->user)
                : $provider->allowsDelivery($link->user, $reportId, $automatic);
        }

        return match ($kind) {
            'notification' => $link->notifications_enabled,
            'broadcast' => $link->broadcasts_enabled,
            'parser' => ! $automatic || $link->exports_enabled,
            'tracking' => ! $automatic,
            default => false,
        };
    }
}
