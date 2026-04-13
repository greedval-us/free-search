<?php

namespace App\Modules\Telegram\Analytics\DTO;

class TelegramAnalyticsParamsDTO
{
    public function __construct(
        public readonly string $chatUsername,
        public readonly string $scorePriority,
        public readonly ?string $keyword,
    ) {
    }
}
