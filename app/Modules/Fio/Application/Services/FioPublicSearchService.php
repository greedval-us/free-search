<?php

namespace App\Modules\Fio\Application\Services;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\FioLookupResultDTO;
use App\Modules\Fio\Domain\Services\FioClusterBuilder;
use App\Modules\Fio\Domain\Services\FioNameNormalizer;
use App\Modules\Fio\Domain\Services\FioSummaryBuilder;

final class FioPublicSearchService
{
    public function __construct(
        private readonly FioPublicSearchProviderInterface $searchProvider,
        private readonly FioNameNormalizer $nameNormalizer,
        private readonly FioMatchAssembler $matchAssembler,
        private readonly FioClusterBuilder $clusterBuilder,
        private readonly FioSummaryBuilder $summaryBuilder,
    ) {
    }

    public function search(string $fullName): FioLookupResultDTO
    {
        $normalizedName = $this->nameNormalizer->normalize($fullName);
        $entries = $this->searchProvider->search($normalizedName);
        $matches = $this->matchAssembler->assembleMany($normalizedName, $entries);
        $regionClusters = $this->clusterBuilder->buildRegionClusters($matches);
        $ageClusters = $this->clusterBuilder->buildAgeClusters($matches);
        $summary = $this->summaryBuilder->build($matches, $regionClusters, $ageClusters);

        return new FioLookupResultDTO(
            fullName: $normalizedName,
            checkedAt: now()->toIso8601String(),
            summary: $summary,
            regionClusters: $regionClusters,
            ageClusters: $ageClusters,
            matches: $matches,
        );
    }
}
