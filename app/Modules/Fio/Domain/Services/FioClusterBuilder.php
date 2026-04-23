<?php

namespace App\Modules\Fio\Domain\Services;

use App\Modules\Fio\Domain\DTO\FioClusterDTO;
use App\Modules\Fio\Domain\DTO\FioMatchDTO;

final class FioClusterBuilder
{
    /**
     * @param array<int, FioMatchDTO> $matches
     * @return array<int, FioClusterDTO>
     */
    public function buildRegionClusters(array $matches): array
    {
        $total = max(1, count($matches));
        $counts = [];

        foreach ($matches as $match) {
            $counts[$match->region] = ($counts[$match->region] ?? 0) + 1;
        }

        arsort($counts);
        $clusters = [];

        foreach ($counts as $key => $count) {
            $clusters[] = new FioClusterDTO(
                key: (string) $key,
                count: (int) $count,
                percent: round(($count / $total) * 100, 1),
            );
        }

        return $clusters;
    }

    /**
     * @param array<int, FioMatchDTO> $matches
     * @return array<int, FioClusterDTO>
     */
    public function buildAgeClusters(array $matches): array
    {
        $total = max(1, count($matches));
        $order = ['under_18', '18_24', '25_34', '35_44', '45_54', '55_plus', 'unknown'];
        $counts = array_fill_keys($order, 0);

        foreach ($matches as $match) {
            $bucket = array_key_exists($match->ageBucket, $counts) ? $match->ageBucket : 'unknown';
            $counts[$bucket]++;
        }

        $clusters = [];

        foreach ($order as $key) {
            $count = (int) ($counts[$key] ?? 0);
            $clusters[] = new FioClusterDTO(
                key: $key,
                count: $count,
                percent: round(($count / $total) * 100, 1),
            );
        }

        return $clusters;
    }
}
