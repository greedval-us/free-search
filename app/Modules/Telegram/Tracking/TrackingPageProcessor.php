<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTrackingSource;

final readonly class TrackingPageProcessor
{
    public function __construct(private TrackingConfig $config, private TrackingMatcher $matcher) {}

    /** @param list<array<string, mixed>> $messages */
    public function process(TelegramTrackingSource $source, array $messages): TrackingPage
    {
        $complete = count($messages) < $this->config->integer('page_size');
        $offset = null;
        $high = $source->high_id;
        $matches = [];
        $from = $source->collect_from->timestamp;
        $until = $source->window_end->timestamp;

        foreach ($messages as $message) {
            $id = (int) ($message['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            // Advance across all message types, including non-matches and service messages.
            $offset = $offset === null ? $id : min($offset, $id);
            $high = max($high, $id);
            $date = (int) ($message['date'] ?? 0);
            if ($id <= $source->cursor_id || ($date > 0 && $date < $from)) {
                $complete = true;

                continue;
            }
            if ($date < $from || $date > $until || ! $this->matcher->matches($source->tracking, $message)) {
                continue;
            }
            $matches[] = [
                'message_id' => $id,
                'sender_id' => isset($message['from_id']['user_id']) ? (string) $message['from_id']['user_id'] : null,
                'text' => (string) ($message['message'] ?? ''),
                'sent_at' => $date,
            ];
        }

        if (! $complete && ($offset === null || ($source->offset_id > 0 && $offset >= $source->offset_id))) {
            throw new TrackingException('pagination_stalled');
        }

        return new TrackingPage($complete, $offset, $high, $matches);
    }
}
