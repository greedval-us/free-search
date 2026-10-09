<?php

namespace App\Integrations\TelegramBot;

use App\Modules\TelegramBot\Application\BotAccess;
use App\Modules\TelegramBot\Models\BotLink;

final readonly class ReportDeliveryStatus
{
    public function __construct(private BotAccess $access) {}

    /** @return array{botLinked: bool, botExportsEnabled: bool} */
    public function forUser(int $userId): array
    {
        $link = BotLink::query()->where('user_id', $userId)->first();

        return ['botLinked' => $this->access->allows($link), 'botExportsEnabled' => $link?->exports_enabled ?? false];
    }
}
