<?php

namespace App\Services\Dashboard;

use App\Models\RequestLog;
use App\Support\Activity\RequestLogRunUrlBuilder;
use Illuminate\Support\Carbon;

class DashboardActivityFeedService
{
    public function __construct(
        private readonly RequestLogRunUrlBuilder $runUrlBuilder,
        private readonly DashboardFilterNormalizer $filterNormalizer,
    ) {
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<RequestLog> $query
     * @param array<string, string> $filters
     * @return array<int, array<string, mixed>>
     */
    public function build($query, array $filters): array
    {
        $query = $this->applyFilters($query, $filters);

        return (clone $query)
            ->latest('created_at')
            ->limit(60)
            ->get()
            ->map(function (RequestLog $record): array {
                $moduleKey = is_string($record->module_key) ? $record->module_key : 'unknown';
                $payload = is_array($record->request_data) ? $record->request_data : [];

                return [
                    'request_log_id' => $record->id,
                    'module_key' => $moduleKey,
                    'query_preview' => $record->query_preview ?: '',
                    'at' => optional($record->created_at)?->toIso8601String(),
                    'run_url' => $this->runUrlBuilder->build(
                        path: $record->path,
                        method: $record->method,
                        payload: $payload,
                    ),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<RequestLog> $query
     * @param array<string, string> $filters
     * @return \Illuminate\Database\Eloquent\Builder<RequestLog>
     */
    private function applyFilters($query, array $filters)
    {
        if ($filters['module_key'] !== '') {
            $query->where('module_key', $filters['module_key']);
        }

        if ($filters['query'] !== '') {
            $query->where('query_preview', 'like', '%'.$filters['query'].'%');
        }

        $hasExplicitDates = $filters['date_from'] !== '' || $filters['date_to'] !== '';

        if (!$hasExplicitDates && $filters['period'] !== '') {
            $days = $this->filterNormalizer->resolvePeriodDays($filters['period']);

            if ($days > 0) {
                $query->where('created_at', '>=', now()->subDays($days));
            }
        }

        if ($filters['date_from'] !== '') {
            try {
                $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
            } catch (\Throwable) {
                // Keep query without date_from filter when date is invalid.
            }
        }

        if ($filters['date_to'] !== '') {
            try {
                $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
            } catch (\Throwable) {
                // Keep query without date_to filter when date is invalid.
            }
        }

        return $query;
    }
}
