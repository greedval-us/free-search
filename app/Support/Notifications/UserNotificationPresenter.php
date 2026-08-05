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
        $data = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'title' => (string) ($data['title'] ?? class_basename($notification->type)),
            'body' => (string) ($data['body'] ?? $data['message'] ?? ''),
            'titleKey' => isset($data['title_key']) ? (string) $data['title_key'] : null,
            'bodyKey' => isset($data['body_key']) ? (string) $data['body_key'] : null,
            'titleParams' => is_array($data['title_params'] ?? null) ? $data['title_params'] : null,
            'bodyParams' => is_array($data['body_params'] ?? null) ? $data['body_params'] : null,
            'url' => $data['url'] ?? null,
            'kind' => (string) ($data['kind'] ?? 'info'),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
