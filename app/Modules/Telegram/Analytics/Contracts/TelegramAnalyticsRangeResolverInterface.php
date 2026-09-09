<?php

namespace App\Modules\Telegram\Analytics\Contracts;

use App\Modules\Telegram\DTO\Request\TelegramAnalyticsRangeDTO;
use Carbon\Carbon;

interface TelegramAnalyticsRangeResolverInterface
{
    /**
     * @return array{from: Carbon, to: Carbon}
     */
    public function resolveRange(TelegramAnalyticsRangeDTO $range): array;

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    public function resolvePreviousRange(Carbon $from, Carbon $to): array;
}
