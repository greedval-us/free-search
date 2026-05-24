<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

use App\Models\RequestLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class AdminControlAnalyticsService
{
    /**
     * @return array<string, int|float>
     */
    public function snapshot(): array
    {
        $now = CarbonImmutable::now();
        $dayAgo = $now->subDay();
        $weekAgo = $now->subDays(7);
        $monthAgo = $now->subDays(30);

        $usersTotal = User::query()->count();
        $usersPremiumTotal = User::query()->where('is_premium', true)->count();
        $usersPremiumActive = User::query()
            ->where('is_premium', true)
            ->where(static function ($query) use ($now): void {
                $query->whereNull('premium_expires_at')
                    ->orWhere('premium_expires_at', '>', $now);
            })
            ->count();

        $requestsTotal = RequestLog::query()->count();
        $requests24h = RequestLog::query()->where('created_at', '>=', $dayAgo)->count();
        $requests7d = RequestLog::query()->where('created_at', '>=', $weekAgo)->count();
        $requests30d = RequestLog::query()->where('created_at', '>=', $monthAgo)->count();

        $avgResponseMs24h = (float) RequestLog::query()
            ->where('created_at', '>=', $dayAgo)
            ->avg('response_time');

        return [
            'users_total' => $usersTotal,
            'users_registered_24h' => User::query()->where('created_at', '>=', $dayAgo)->count(),
            'users_registered_7d' => User::query()->where('created_at', '>=', $weekAgo)->count(),
            'users_registered_30d' => User::query()->where('created_at', '>=', $monthAgo)->count(),
            'users_premium_total' => $usersPremiumTotal,
            'users_premium_active' => $usersPremiumActive,
            'users_blocked' => User::query()->where('is_blocked', true)->count(),
            'users_active_24h' => RequestLog::query()
                ->where('created_at', '>=', $dayAgo)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'users_active_7d' => RequestLog::query()
                ->where('created_at', '>=', $weekAgo)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'requests_total' => $requestsTotal,
            'requests_24h' => $requests24h,
            'requests_7d' => $requests7d,
            'requests_30d' => $requests30d,
            'errors_4xx_24h' => RequestLog::query()
                ->where('created_at', '>=', $dayAgo)
                ->whereBetween('status_code', [400, 499])
                ->count(),
            'errors_5xx_24h' => RequestLog::query()
                ->where('created_at', '>=', $dayAgo)
                ->whereBetween('status_code', [500, 599])
                ->count(),
            'modules_used_30d' => RequestLog::query()
                ->where('created_at', '>=', $monthAgo)
                ->whereNotNull('module_key')
                ->where('module_key', '<>', '')
                ->distinct('module_key')
                ->count('module_key'),
            'avg_response_ms_24h' => round($avgResponseMs24h, 2),
        ];
    }

    /**
     * @return array<int, array{
     *   module_label: string,
     *   requests_count: int,
     *   users_count: int,
     *   errors_4xx: int,
     *   errors_5xx: int
     * }>
     */
    public function topModules(int $days = 30, int $limit = 8): array
    {
        $query = RequestLog::query()
            ->selectRaw("COALESCE(NULLIF(module_key, ''), 'unknown') as module_label")
            ->selectRaw('COUNT(*) as requests_count')
            ->selectRaw('COUNT(DISTINCT user_id) as users_count')
            ->selectRaw('SUM(CASE WHEN status_code BETWEEN 400 AND 499 THEN 1 ELSE 0 END) as errors_4xx')
            ->selectRaw('SUM(CASE WHEN status_code BETWEEN 500 AND 599 THEN 1 ELSE 0 END) as errors_5xx')
            ->groupByRaw("COALESCE(NULLIF(module_key, ''), 'unknown')")
            ->orderByDesc('requests_count')
            ->limit($limit);

        if ($days > 0) {
            $query->where('created_at', '>=', CarbonImmutable::now()->subDays($days));
        }

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows
            ->map(static fn (object $row): array => [
                'module_label' => (string) $row->module_label,
                'requests_count' => (int) $row->requests_count,
                'users_count' => (int) $row->users_count,
                'errors_4xx' => (int) $row->errors_4xx,
                'errors_5xx' => (int) $row->errors_5xx,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{
     *   date: string,
     *   registrations_count: int,
     *   requests_count: int,
     *   active_users_count: int
     * }>
     */
    public function dailyActivity(int $days = 7): array
    {
        $days = max($days, 1);

        $start = CarbonImmutable::today()->subDays($days - 1);

        $requestRows = RequestLog::query()
            ->selectRaw('date(created_at) as day')
            ->selectRaw('COUNT(*) as requests_count')
            ->selectRaw('COUNT(DISTINCT user_id) as active_users_count')
            ->where('created_at', '>=', $start)
            ->groupByRaw('date(created_at)')
            ->get()
            ->keyBy('day');

        $userRows = User::query()
            ->selectRaw('date(created_at) as day')
            ->selectRaw('COUNT(*) as registrations_count')
            ->where('created_at', '>=', $start)
            ->groupByRaw('date(created_at)')
            ->get()
            ->keyBy('day');

        $result = [];

        for ($date = $start; $date->lte(CarbonImmutable::today()); $date = $date->addDay()) {
            $key = $date->format('Y-m-d');
            $requestRow = $requestRows->get($key);
            $userRow = $userRows->get($key);

            $result[] = [
                'date' => $date->format('d.m.Y'),
                'registrations_count' => (int) ($userRow->registrations_count ?? 0),
                'requests_count' => (int) ($requestRow->requests_count ?? 0),
                'active_users_count' => (int) ($requestRow->active_users_count ?? 0),
            ];
        }

        return $result;
    }
}
