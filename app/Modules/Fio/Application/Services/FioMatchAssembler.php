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
    public function assembleMany(string $fullName, array $entries, ?string $qualifier = null): array
    {
        $matches = [];

        foreach ($entries as $entry) {
            $searchText = trim($entry->title . ' ' . $entry->snippet . ' ' . $entry->url);
            $age = $this->ageAnalyzer->extractAge($searchText);
            $qualifierMatched = $this->confidenceScorer->qualifierMatched($searchText, $qualifier);

            $matches[] = new FioMatchDTO(
                title: $entry->title,
                snippet: $entry->snippet,
                url: $entry->url,
                domain: $entry->domain,
                source: $entry->source,
                sourceReliability: $this->confidenceScorer->sourceReliability($entry->source),
                region: $this->regionResolver->resolve($searchText),
                age: $age,
                ageBucket: $this->ageAnalyzer->resolveBucket($age),
                qualifier: $qualifier,
                qualifierMatched: $qualifierMatched,
                confidence: $this->confidenceScorer->score($fullName, $searchText, $entry->source, $qualifier),
            );
        }

        return $matches;
    }
}
