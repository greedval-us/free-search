<?php

namespace App\Support\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final class UserNotificationPresenter
{
    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return array<int, array<string, mixed>>
     */
    public function presentCollection(Collection $notifications): array
    {
        return $notifications
            ->map(fn (DatabaseNotification $notification): array => $this->present($notification))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function present(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => (string) ($notification->data['title'] ?? class_basename($notification->type)),
            'body' => (string) ($notification->data['body'] ?? $notification->data['message'] ?? ''),
            'url' => $notification->data['url'] ?? null,
            'kind' => (string) ($notification->data['kind'] ?? 'info'),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
