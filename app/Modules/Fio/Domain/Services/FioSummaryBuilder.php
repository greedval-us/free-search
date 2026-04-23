<?php

namespace App\Modules\Fio\Domain\Services;

use App\Modules\Fio\Domain\DTO\FioClusterDTO;
use App\Modules\Fio\Domain\DTO\FioMatchDTO;
use App\Modules\Fio\Domain\DTO\FioSummaryDTO;

final class FioSummaryBuilder
{
    /**
     * @param array<int, FioMatchDTO> $matches
     * @param array<int, FioClusterDTO> $regionClusters
     * @param array<int, FioClusterDTO> $ageClusters
     */
    public function build(array $matches, array $regionClusters, array $ageClusters): FioSummaryDTO
    {
        $domains = [];
        $ages = [];
        $confidenceTotal = 0;
        $qualifierMatches = 0;

        foreach ($matches as $match) {
            if (is_string($match->domain) && $match->domain !== '') {
                $domains[$match->domain] = true;
            }

            if (is_int($match->age)) {
                $ages[] = $match->age;
            }

            $confidenceTotal += $match->confidence;
            if ($match->qualifierMatched) {
                $qualifierMatches++;
            }
        }

        $matchesCount = count($matches);
        $averageConfidence = $matchesCount > 0 ? round($confidenceTotal / $matchesCount, 1) : 0.0;

        return new FioSummaryDTO(
            matches: $matchesCount,
            domains: count($domains),
            topRegion: $regionClusters[0]->key ?? 'unknown',
            topAgeBucket: $this->resolveTopAgeBucket($ageClusters),
            medianAge: $this->resolveMedianAge($ages),
            averageConfidence: $averageConfidence,
            qualifierMatches: $qualifierMatches,
        );
    }

    /**
     * @param array<int, int> $ages
     */
    private function resolveMedianAge(array $ages): ?int
    {
        if (count($ages) === 0) {
            return null;
        }

        sort($ages);
        $middle = intdiv(count($ages), 2);

        if (count($ages) % 2 === 0) {
            return (int) round(($ages[$middle - 1] + $ages[$middle]) / 2);
        }

        return $ages[$middle];
    }

    /**
     * @param array<int, FioClusterDTO> $ageClusters
     */
    private function resolveTopAgeBucket(array $ageClusters): string
    {
        $topKey = 'unknown';
        $topCount = -1;

        foreach ($ageClusters as $cluster) {
            if ($cluster->count > $topCount) {
                $topCount = $cluster->count;
                $topKey = $cluster->key;
            }
        }

        return $topKey;
    }
}
