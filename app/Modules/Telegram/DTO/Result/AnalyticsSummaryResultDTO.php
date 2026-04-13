<?php

namespace App\Modules\Telegram\DTO\Result;

class AnalyticsSummaryResultDTO
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly array $data,
    ) {
    }
}
