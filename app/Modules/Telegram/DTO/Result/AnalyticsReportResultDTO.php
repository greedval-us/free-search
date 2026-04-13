<?php

namespace App\Modules\Telegram\DTO\Result;

class AnalyticsReportResultDTO
{
    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $previousReport
     */
    public function __construct(
        public readonly array $report,
        public readonly array $previousReport,
    ) {
    }
}
