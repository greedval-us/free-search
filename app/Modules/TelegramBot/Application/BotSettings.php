<?php

namespace App\Modules\TelegramBot\Application;

use App\Models\User;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Models\LinkRequest;
use App\Modules\TelegramBot\Support\BotConfig;

final readonly class BotSettings
{
    public function __construct(private BotConfig $config) {}

    public function state(User $user): array
    {
        $link = BotLink::query()->where('user_id', $user->id)->first();
        $pending = LinkRequest::query()->where('user_id', $user->id)->where('expires_at', '>', now())->first();

        return [
            'available' => $this->config->active(),
            'username' => $this->config->username(),
            'link' => $link?->only(['telegram_id', 'locale', 'notifications_enabled', 'exports_enabled', 'broadcasts_enabled']),
            'pending' => $pending?->only(['id', 'telegram_id', 'expires_at']),
        ];
    }

    /** @param array<string, mixed> $preferences */
    public function update(User $user, array $preferences): void
    {
        BotLink::query()->where('user_id', $user->id)->firstOrFail()
            ->update(array_intersect_key($preferences, array_flip(['locale', 'notifications_enabled', 'exports_enabled', 'broadcasts_enabled'])));
    }
}
