<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTracking;
use App\Models\TelegramTrackingSource;
use App\Models\User;
use App\Notifications\SystemDatabaseNotification;

final class TrackingNotifications
{
    public function paused(User $user, TelegramTracking $task): void
    {
        $this->send($user, $task, 'trackingPaused');
    }

    public function matches(User $user, TelegramTrackingSource $source, int $count): void
    {
        if ($count > 0) {
            $this->send($user, $source->tracking, 'trackingMatches', ['count' => $count, 'group' => $source->title]);
        }
    }

    /** @param array<string, int|string> $parameters */
    private function send(User $user, TelegramTracking $task, string $event, array $parameters = []): void
    {
        $user->notify(new SystemDatabaseNotification([
            'title_key' => 'systemNotifications.'.$event.'.title',
            'body_key' => 'systemNotifications.'.$event.'.body',
            'body_params' => ['name' => $task->name, ...$parameters],
            'kind' => 'tracking',
            'url' => '/telegram?tab=tracking',
            'telegram_bot' => $task->notify_bot,
        ]));
    }
}
