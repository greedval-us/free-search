<?php

namespace App\Modules\Telegram\Analytics\Contracts;

use App\Http\Requests\Telegram\TelegramAnalyticsRequest;
use Carbon\Carbon;

interface TelegramAnalyticsRangeResolverInterface
{
    /**
     * @return array{from: Carbon, to: Carbon}
     */
    public function resolveRange(TelegramAnalyticsRequest $request): array;

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    public function resolvePreviousRange(Carbon $from, Carbon $to): array;

    /**
     * @param array<string, mixed> $report
     * @return array{from: Carbon, to: Carbon}
     */
    public function resolvePreviousRangeForReport(array $report, Carbon $fallbackFrom, Carbon $fallbackTo): array;
}

