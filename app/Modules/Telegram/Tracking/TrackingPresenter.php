<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTracking;
use App\Models\TelegramTrackingMessage;
use App\Models\TelegramTrackingSource;

final readonly class TrackingPresenter
{
    public function __construct(private TrackingConfig $config) {}

    public function task(TelegramTracking $task): array
    {
        return [
            'id' => $task->id, 'name' => $task->name, 'mode' => $task->mode, 'query' => $task->query,
            'status' => $task->status, 'pause_reason' => $task->pause_reason, 'notify_bot' => $task->notify_bot,
            'started_at' => $task->started_at->toIso8601String(), 'expires_at' => $task->expires_at->toIso8601String(),
            'purge_at' => $task->purge_at?->toIso8601String(), 'messages_count' => $task->messages_count ?? $task->messages()->count(),
            'can_renew' => $task->canRenew($this->config->integer('renewal_window_days')),
            'sources' => $task->sources->map(fn (TelegramTrackingSource $source) => [
                'id' => $source->id, 'title' => $source->title, 'peer_id' => $source->peer_id, 'username' => $source->username,
                'checked_at' => $source->checked_at?->toIso8601String(), 'next_check_at' => $source->next_check_at->toIso8601String(),
                'collecting' => $source->window_end !== null, 'error_code' => $source->error_code,
                'overdue' => $task->status === TelegramTracking::ACTIVE
                    && ($source->checked_at ?? $source->collect_from)->lt(now()->subHours($this->config->integer('max_interval_hours'))),
            ])->all(),
        ];
    }

    public static function message(TelegramTrackingMessage $message): array
    {
        $source = $message->source;
        $username = $source->username;
        $url = is_string($username) && preg_match('/^[a-zA-Z0-9_]+$/', $username)
            ? 'https://t.me/'.$username.'/'.$message->message_id : null;

        return ['id' => $message->id, 'message_id' => (string) $message->message_id, 'group' => $source->title,
            'peer_id' => $source->peer_id, 'sender_id' => $message->sender_id, 'text' => $message->text,
            'sent_at' => $message->sent_at->toIso8601String(), 'received_at' => $message->received_at->toIso8601String(), 'url' => $url];
    }
}
