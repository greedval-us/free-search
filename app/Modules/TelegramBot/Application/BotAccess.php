<?php

namespace App\Modules\TelegramBot\Application;

use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;

final readonly class BotAccess
{
    public function __construct(private BotConfig $config, private FeatureAccessServiceInterface $featureAccess) {}

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

    public function allowsDelivery(?BotLink $link, string $kind, bool $automatic): bool
    {
        if (! $this->allows($link)) {
            return false;
        }

        return match ($kind) {
            'notification' => $link->notifications_enabled,
            'broadcast' => $link->broadcasts_enabled,
            'parser' => ! $automatic || $link->exports_enabled,
            'analytics_report' => (! $automatic || $link->exports_enabled)
                && $this->featureAccess->inspect($link->user, 'telegram.analytics', false)->allowed,
            'youtube_analytics_report' => (! $automatic || $link->exports_enabled)
                && $this->featureAccess->inspect($link->user, 'youtube.analytics', false)->allowed,
            'bluesky_analytics_report' => (! $automatic || $link->exports_enabled)
                && $this->featureAccess->inspect($link->user, 'bluesky.analytics', false)->allowed,
            'mastodon_analytics_report' => (! $automatic || $link->exports_enabled)
                && $this->featureAccess->inspect($link->user, 'mastodon.analytics', false)->allowed,
            'tracking' => ! $automatic,
            default => false,
        };
    }
}
