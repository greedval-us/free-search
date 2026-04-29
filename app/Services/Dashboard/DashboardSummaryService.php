<?php

namespace App\Services\Dashboard;

use App\Models\RequestLog;
use App\Support\Dashboard\DashboardModuleRegistry;
use Illuminate\Support\Carbon;

class DashboardSummaryService
{
    public function __construct(
        private readonly DashboardModuleRegistry $moduleRegistry,
    ) {
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<RequestLog> $query
     * @return array<string, int>
     */
    public function buildSummary($query): array
    {
        $now = now();

        return [
            'total_actions' => (clone $query)->count(),
            'actions_last_7_days' => (clone $query)->where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'actions_last_30_days' => (clone $query)->where('created_at', '>=', $now->copy()->subDays(30))->count(),
            'active_days_last_30_days' => (clone $query)
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw('DATE(created_at) as day')
                ->groupBy('day')
                ->get()
                ->count(),
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<RequestLog> $query
     * @return array<string, mixed>|null
     */
    public function buildFavoriteModule($query): ?array
    {
        $record = (clone $query)
            ->selectRaw('module_key, COUNT(*) as total')
            ->groupBy('module_key')
            ->orderByDesc('total')
            ->first();

        if (!$record instanceof RequestLog || !is_string($record->module_key)) {
            return null;
        }

        return [
            'key' => $record->module_key,
            'total' => (int) $record->getAttribute('total'),
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<RequestLog> $query
     * @param array<string, bool> $pinnedLookup
     * @return array<int, array<string, mixed>>
     */
    public function buildModuleCards($query, array $pinnedLookup): array
    {
        $rows = (clone $query)
            ->selectRaw('module_key, COUNT(*) as total, MAX(created_at) as last_at')
            ->groupBy('module_key')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(function (RequestLog $record) use ($pinnedLookup): array {
                $moduleKey = is_string($record->module_key) ? $record->module_key : 'unknown';

                return [
                    'key' => $moduleKey,
                    'count' => (int) $record->getAttribute('total'),
                    'last_at' => $this->toIsoDateTime($record->getAttribute('last_at')),
                    'url' => $this->moduleRegistry->resolveUrl($moduleKey),
                    'is_pinned' => (bool) ($pinnedLookup[$moduleKey] ?? false),
                ];
            })
            ->values()
            ->all();

        usort($rows, function (array $a, array $b): int {
            if ((bool) $a['is_pinned'] !== (bool) $b['is_pinned']) {
                return (bool) $a['is_pinned'] ? -1 : 1;
            }

            return (int) $b['count'] <=> (int) $a['count'];
        });

        return $rows;
    }

    private function toIsoDateTime(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return Carbon::parse($value)->toIso8601String();
        }

        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        return null;
    }
}

