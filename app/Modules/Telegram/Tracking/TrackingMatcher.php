<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTracking;

final class TrackingMatcher
{
    public function matches(TelegramTracking $tracking, array $message): bool
    {
        if (($message['_'] ?? '') !== 'message') {
            return false;
        }
        if ($tracking->mode === 'user') {
            return ($message['from_id']['_'] ?? '') === 'peerUser'
                && (string) ($message['from_id']['user_id'] ?? '') === $tracking->query;
        }

        return mb_stripos((string) ($message['message'] ?? ''), $tracking->query) !== false;
    }
}
