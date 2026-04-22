<?php

namespace App\Modules\Fio\Domain\DTO;

final class FioLookupResultDTO
{
    /**
     * @param array<int, FioClusterDTO> $regionClusters
     * @param array<int, FioClusterDTO> $ageClusters
     * @param array<int, FioMatchDTO> $matches
     */
    public function __construct(
        public readonly string $fullName,
        public readonly string $checkedAt,
        public readonly FioSummaryDTO $summary,
        public readonly array $regionClusters,
        public readonly array $ageClusters,
        public readonly array $matches,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'target' => [
                'fullName' => $this->fullName,
            ],
            'checkedAt' => $this->checkedAt,
            'summary' => $this->summary->toArray(),
            'clusters' => [
                'regions' => array_map(
                    static fn (FioClusterDTO $cluster): array => $cluster->toArray(),
                    $this->regionClusters
                ),
                'ages' => array_map(
                    static fn (FioClusterDTO $cluster): array => $cluster->toArray(),
                    $this->ageClusters
                ),
            ],
            'matches' => array_map(
                static fn (FioMatchDTO $match): array => $match->toArray(),
                $this->matches
            ),
            'source' => [
                'provider' => 'DuckDuckGo HTML',
                'mode' => 'online-public',
            ],
        ];
    }
}
