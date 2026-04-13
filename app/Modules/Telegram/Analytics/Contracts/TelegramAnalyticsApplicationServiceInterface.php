<?php

namespace App\Modules\Telegram\Analytics\Contracts;

use App\Modules\Telegram\DTO\Request\TelegramAnalyticsParamsDTO;
use App\Modules\Telegram\DTO\Result\AnalyticsReportResultDTO;
use App\Modules\Telegram\DTO\Result\AnalyticsSummaryResultDTO;
use Carbon\Carbon;

interface TelegramAnalyticsApplicationServiceInterface
{
    public function buildSummary(
        TelegramAnalyticsParamsDTO $params,
        Carbon $from,
        Carbon $to,
        ?string $snapshotRole = null
    ): AnalyticsSummaryResultDTO;

    public function buildReport(
        TelegramAnalyticsParamsDTO $params,
        Carbon $from,
        Carbon $to
    ): AnalyticsReportResultDTO;
}
