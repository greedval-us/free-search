<?php

namespace App\Services\Dashboard;

use App\Models\RequestLog;

class DashboardChartService
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<RequestLog> $query
     * @return array<int, array<string, mixed>>
     */
    public function build($query, int $days): array
    {
        $startDay = now()->startOfDay()->subDays($days - 1);

        /** @var \Illuminate\Support\Collection<string, int> $rows */
        $rows = (clone $query)
            ->where('created_at', '>=', $startDay)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $startDay->copy()->addDays($i);
            $key = $day->toDateString();

            $result[] = [
                'date' => $key,
                'day' => $day->format('D'),
                'count' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildEmpty(int $days): array
    {
        $startDay = now()->startOfDay()->subDays($days - 1);
        $result = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $startDay->copy()->addDays($i);
            $result[] = [
                'date' => $day->toDateString(),
                'day' => $day->format('D'),
                'count' => 0,
            ];
        }

        return $result;
    }
}

