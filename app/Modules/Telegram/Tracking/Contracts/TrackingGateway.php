<?php

namespace App\Modules\Telegram\Tracking\Contracts;

use App\Models\TelegramTrackingSource;

interface TrackingGateway
{
    /** @param list<string> $groups
     * @return list<array{session_name: string, peer_id: string, username: ?string, title: string}>
     */
    public function resolve(array $groups, ?string $keyword = null): array;

    /** @return list<array<string, mixed>> */
    public function fetch(TelegramTrackingSource $source): array;
}
