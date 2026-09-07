<?php

namespace App\Modules\Telegram\Analytics;

use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsRangeResolverInterface;
use App\Modules\Telegram\DTO\Request\TelegramAnalyticsRangeDTO;
use App\Modules\Telegram\Support\TelegramConfig;
use Carbon\Carbon;

class TelegramAnalyticsRangeResolver implements TelegramAnalyticsRangeResolverInterface
{
    public function __construct(private readonly TelegramConfig $config)
    {
    }

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
        $spanSeconds = max(0, $normalizedTo->diffInSeconds($normalizedFrom));

        $previousTo = $normalizedFrom->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subSeconds($spanSeconds);

        return [
            'from' => $previousFrom,
            'to' => $previousTo,
        ];
    }

    /**
     * @param array<string, mixed> $report
     * @return array{from: Carbon, to: Carbon}
     */
    public function resolvePreviousRangeForReport(array $report, Carbon $fallbackFrom, Carbon $fallbackTo): array
    {
        $currentFromIso = data_get($report, 'range.dateFrom');
        $currentToIso = data_get($report, 'range.dateTo');

        if (!is_string($currentFromIso) || !is_string($currentToIso)) {
            return $this->resolvePreviousRange($fallbackFrom, $fallbackTo);
        }

        try {
            $currentFromUtc = Carbon::parse($currentFromIso)->utc();
            $currentToUtc = Carbon::parse($currentToIso)->utc();
            $spanSeconds = max(0, $currentToUtc->diffInSeconds($currentFromUtc));

            $previousToUtc = $currentFromUtc->copy()->subSecond();
            $previousFromUtc = $previousToUtc->copy()->subSeconds($spanSeconds);

            // Keep report previous period aligned with frontend date-only summary query.
            $previousDateFrom = $previousFromUtc->format('Y-m-d');
            $previousDateTo = $previousToUtc->format('Y-m-d');
            $timezone = $this->config->timezone();

            return [
                'from' => Carbon::createFromFormat('Y-m-d', $previousDateFrom, $timezone)->startOfDay(),
                'to' => Carbon::createFromFormat('Y-m-d', $previousDateTo, $timezone)->endOfDay(),
            ];
        } catch (\Throwable) {
            return $this->resolvePreviousRange($fallbackFrom, $fallbackTo);
        }
    }
}
