<?php

namespace App\Services\Dashboard;

use App\Support\Dashboard\DashboardModuleRegistry;

class DashboardFilterNormalizer
{
    /**
     * @var array<string, int>
     */
    private array $periodDays = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
    ];

    public function __construct(
        private readonly DashboardModuleRegistry $moduleRegistry,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, string>
     */
    public function normalize(array $filters): array
    {
        $moduleKey = (string) ($filters['module_key'] ?? '');
        $query = trim((string) ($filters['query'] ?? ''));
        $period = (string) ($filters['period'] ?? '30d');
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        if (!$this->moduleRegistry->isSupported($moduleKey)) {
            $moduleKey = '';
        }

        if (!array_key_exists($period, $this->periodDays)) {
            $period = '30d';
        }

        return [
            'module_key' => $moduleKey,
            'query' => mb_substr($query, 0, 120),
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    public function resolvePeriodDays(string $period): int
    {
        return $this->periodDays[$period] ?? 0;
    }
}
