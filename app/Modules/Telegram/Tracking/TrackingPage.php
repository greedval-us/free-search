<?php

namespace App\Modules\Telegram\Tracking;

final readonly class TrackingPage
{
    /**
     * @param  list<array{message_id: int, sender_id: ?string, text: string, sent_at: int}>  $matches
     */
    public function __construct(
        public bool $complete,
        public ?int $offsetId,
        public int $highId,
        public array $matches,
    ) {}
}
