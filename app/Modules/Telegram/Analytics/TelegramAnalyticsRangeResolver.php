<?php

namespace App\Modules\Telegram\Analytics;

use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsRangeResolverInterface;
use App\Modules\Telegram\DTO\Request\TelegramAnalyticsRangeDTO;
use App\Modules\Telegram\Support\TelegramConfig;
use Carbon\Carbon;

class TelegramAnalyticsRangeResolver implements TelegramAnalyticsRangeResolverInterface
{
    public function __construct(private readonly TelegramConfig $config) {}

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    public function resolveRange(TelegramAnalyticsRangeDTO $range): array
    {
        if ($range->hasCustomRange()) {
            $timezone = $this->config->timezone();

            return [
                'from' => Carbon::createFromFormat('Y-m-d', $range->dateFrom, $timezone)->startOfDay(),
                'to' => Carbon::createFromFormat('Y-m-d', $range->dateTo, $timezone)->endOfDay(),
            ];
        }

        $to = Carbon::now($this->config->timezone())->endOfDay();
        $from = $to->copy()->subDays($range->periodDays - 1)->startOfDay();

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    public function resolvePreviousRange(Carbon $from, Carbon $to): array
    {
        $normalizedFrom = $from->copy()->startOfSecond();
        $normalizedTo = $to->copy()->startOfSecond();
        $spanSeconds = max(0, $normalizedFrom->diffInSeconds($normalizedTo));

        $previousTo = $normalizedFrom->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subSeconds($spanSeconds);

        return [
            'from' => $previousFrom,
            'to' => $previousTo,
        ];
    }
}
