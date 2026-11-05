<?php

namespace App\Http\Responses;

use App\Models\ScheduledAnalyticsReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class ScheduledAnalyticsReportHistory
{
    private const PUBLIC_ERRORS = [
        'account_unavailable', 'access_denied', 'disabled', 'generation_failed',
        'attempts_exhausted', 'collection_limit', 'pagination_stalled',
    ];

    /** @param LengthAwarePaginator<int, ScheduledAnalyticsReport> $reports */
    public static function page(LengthAwarePaginator $reports, string $configGroup): array
    {
        return [
            'data' => $reports->getCollection()->map(
                fn (ScheduledAnalyticsReport $report) => self::row($report, $configGroup),
            ),
            'currentPage' => $reports->currentPage(),
            'lastPage' => $reports->lastPage(),
            'total' => $reports->total(),
            'perPage' => $reports->perPage(),
        ];
    }

    private static function row(ScheduledAnalyticsReport $report, string $configGroup): array
    {
        $targetColumn = $report->targetColumn();
        $timezone = $report->schedule?->timezone ?? (string) config($configGroup.'.timezone', 'Europe/Moscow');
        $reason = in_array($report->error_code, self::PUBLIC_ERRORS, true) ? $report->error_code : 'generation_failed';

        return [
            'id' => $report->id,
            'scheduleId' => $report->schedule_id,
            'scheduleName' => $report->schedule?->name,
            Str::camel($targetColumn) => $report->getAttribute($targetColumn),
            'scheduledFor' => $report->scheduled_for?->toIso8601String(),
            'dateFrom' => $report->date_from?->setTimezone($timezone)->toIso8601String(),
            'dateTo' => $report->date_to?->setTimezone($timezone)->toIso8601String(),
            'status' => $report->status,
            'errorCode' => $report->error_code,
            'errorMessage' => $report->error_code === null ? null : __($configGroup.'.errors.'.$reason),
            'completedAt' => $report->completed_at?->toIso8601String(),
        ];
    }
}
