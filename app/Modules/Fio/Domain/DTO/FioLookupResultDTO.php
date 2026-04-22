<?php

namespace App\Modules\Fio\Domain\DTO;

final class FioLookupResultDTO
{
    /**
     * @param array<int, FioClusterDTO> $regionClusters
     * @param array<int, FioClusterDTO> $ageClusters
     * @param array<int, FioMatchDTO> $matches
     * @param array<int, array<string, mixed>> $sourceStats
     * @param array<int, string> $qualifierTerms
     */
    public function __construct(
        public readonly string $fullName,
        public readonly ?string $qualifier,
        public readonly array $qualifierTerms,
        public readonly string $checkedAt,
        public readonly FioSummaryDTO $summary,
        public readonly array $regionClusters,
        public readonly array $ageClusters,
        public readonly array $matches,
        public readonly array $sourceStats,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $providers = [];

        foreach ($this->matches as $match) {
            $providers[$match->source] = true;
        }

        return [
            'target' => [
                'fullName' => $this->fullName,
                'qualifier' => $this->qualifier,
                'qualifierTerms' => $this->qualifierTerms,
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
                'provider' => 'multi-source',
                'mode' => 'online-public',
                'providers' => array_values(array_keys($providers)),
                'stats' => $this->sourceStats,
            ],
        ];
    }
}
