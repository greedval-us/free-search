<?php

namespace App\Modules\Telegram\DTO\Request;

final readonly class TelegramAnalyticsRangeDTO
{
    public function __construct(
        public int $periodDays,
        public ?string $dateFrom,
        public ?string $dateTo,
    ) {}

    public function hasCustomRange(): bool
    {
        return $this->dateFrom !== null && $this->dateTo !== null;
    }
}
