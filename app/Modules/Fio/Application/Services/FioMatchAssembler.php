<?php

namespace App\Modules\Fio\Application\Services;

use App\Modules\Fio\Domain\DTO\FioMatchDTO;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Domain\Services\FioAgeAnalyzer;
use App\Modules\Fio\Domain\Services\FioConfidenceScorer;
use App\Modules\Fio\Domain\Services\FioRegionResolver;

final class FioMatchAssembler
{
    public function __construct(
        private readonly FioAgeAnalyzer $ageAnalyzer,
        private readonly FioRegionResolver $regionResolver,
        private readonly FioConfidenceScorer $confidenceScorer,
    ) {
    }

    /**
     * @param array<int, PublicSearchEntryDTO> $entries
     * @return array<int, FioMatchDTO>
     */
    public function assembleMany(string $fullName, array $entries): array
    {
        $matches = [];

        foreach ($entries as $entry) {
            $searchText = trim($entry->title . ' ' . $entry->snippet . ' ' . $entry->url);
            $age = $this->ageAnalyzer->extractAge($searchText);

            $matches[] = new FioMatchDTO(
                title: $entry->title,
                snippet: $entry->snippet,
                url: $entry->url,
                domain: $entry->domain,
                source: $entry->source,
                region: $this->regionResolver->resolve($searchText),
                age: $age,
                ageBucket: $this->ageAnalyzer->resolveBucket($age),
                confidence: $this->confidenceScorer->score($fullName, $searchText),
            );
        }

        return $matches;
    }
}
