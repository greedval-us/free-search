<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTracking;

final class TrackingMatcher
{
    public function matches(TelegramTracking $tracking, array $message, bool $searchResult = false): bool
    {
        if (($message['_'] ?? '') !== 'message') {
            return false;
        }
        if ($tracking->mode === 'user') {
            return ($message['from_id']['_'] ?? '') === 'peerUser'
                && (string) ($message['from_id']['user_id'] ?? '') === $tracking->query;
        }

        // Search uses Telegram's matching rules; substring matching remains for in-flight legacy history.
        return $searchResult || mb_stripos((string) ($message['message'] ?? ''), $tracking->query) !== false;
    }
}
