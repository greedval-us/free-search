<?php

namespace App\Modules\Fio\Application\Services;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\Contracts\FioSearchDiagnosticsAwareInterface;
use App\Modules\Fio\Domain\DTO\FioLookupResultDTO;
use App\Modules\Fio\Domain\Services\FioClusterBuilder;
use App\Modules\Fio\Domain\Services\FioNameNormalizer;
use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use App\Modules\Fio\Domain\Services\FioSummaryBuilder;

final class FioPublicSearchService
{
    public function __construct(
        private readonly FioPublicSearchProviderInterface $searchProvider,
        private readonly FioNameNormalizer $nameNormalizer,
        private readonly FioQualifierLexicon $qualifierLexicon,
        private readonly FioMatchAssembler $matchAssembler,
        private readonly FioClusterBuilder $clusterBuilder,
        private readonly FioSummaryBuilder $summaryBuilder,
    ) {
    }

    public function search(string $fullName, ?string $qualifier = null): FioLookupResultDTO
    {
        $normalizedName = $this->nameNormalizer->normalize($fullName);
        $normalizedQualifier = $this->qualifierLexicon->normalize($qualifier);
        $qualifierTerms = $this->qualifierLexicon->queryTerms($normalizedQualifier, 6);

        $entries = $this->searchProvider->search($normalizedName, $normalizedQualifier);
        $matches = $this->matchAssembler->assembleMany($normalizedName, $entries, $normalizedQualifier);
        $matches = $this->filterRelevantMatches($normalizedName, $matches);
        $regionClusters = $this->clusterBuilder->buildRegionClusters($matches);
        $ageClusters = $this->clusterBuilder->buildAgeClusters($matches);
        $summary = $this->summaryBuilder->build($matches, $regionClusters, $ageClusters);
        $sourceStats = $this->buildSourceStats($matches);
        $diagnostics = $this->searchProvider instanceof FioSearchDiagnosticsAwareInterface
            ? $this->searchProvider->diagnostics()
            : ['attemptedSources' => [], 'sourceErrors' => []];

        return new FioLookupResultDTO(
            fullName: $normalizedName,
            qualifier: $normalizedQualifier,
            qualifierTerms: $qualifierTerms,
            checkedAt: now()->toIso8601String(),
            summary: $summary,
            regionClusters: $regionClusters,
            ageClusters: $ageClusters,
            matches: $matches,
            sourceStats: $sourceStats,
            attemptedSources: is_array($diagnostics['attemptedSources'] ?? null) ? $diagnostics['attemptedSources'] : [],
            sourceErrors: is_array($diagnostics['sourceErrors'] ?? null) ? $diagnostics['sourceErrors'] : [],
        );
    }

    /**
     * @param array<int, \App\Modules\Fio\Domain\DTO\FioMatchDTO> $matches
     * @return array<int, array<string, mixed>>
     */
    private function buildSourceStats(array $matches): array
    {
        $buckets = [];

        foreach ($matches as $match) {
            if (!isset($buckets[$match->source])) {
                $buckets[$match->source] = [
                    'source' => $match->source,
                    'reliability' => $match->sourceReliability,
                    'matches' => 0,
                    'qualifierMatches' => 0,
                    'confidenceTotal' => 0,
                ];
            }

            $buckets[$match->source]['matches']++;
            $buckets[$match->source]['confidenceTotal'] += $match->confidence;
            if ($match->qualifierMatched) {
                $buckets[$match->source]['qualifierMatches']++;
            }
        }

        $result = [];
        foreach ($buckets as $bucket) {
            $matchesCount = (int) $bucket['matches'];
            $avg = $matchesCount > 0 ? round(((int) $bucket['confidenceTotal']) / $matchesCount, 1) : 0.0;
            $result[] = [
                'source' => $bucket['source'],
                'reliability' => $bucket['reliability'],
                'matches' => $matchesCount,
                'qualifierMatches' => (int) $bucket['qualifierMatches'],
                'averageConfidence' => $avg,
            ];
        }

        usort($result, static fn (array $a, array $b): int => $b['matches'] <=> $a['matches']);

        return $result;
    }

    /**
     * @param  array<int, \App\Modules\Fio\Domain\DTO\FioMatchDTO>  $matches
     * @return array<int, \App\Modules\Fio\Domain\DTO\FioMatchDTO>
     */
    private function filterRelevantMatches(string $fullName, array $matches): array
    {
        $minConfidence = 45;
        $nameParts = $this->extractNameParts($fullName);
        $requiredHits = count($nameParts) >= 3 ? 2 : max(1, count($nameParts));

        $filtered = array_values(array_filter(
            $matches,
            function ($match) use ($nameParts, $requiredHits, $minConfidence): bool {
                if ($match->confidence < $minConfidence) {
                    return false;
                }

                $text = mb_strtolower(trim($match->title . ' ' . $match->snippet . ' ' . $match->url));
                $nameHits = $this->countNameHits($text, $nameParts);
                if ($nameHits < $requiredHits) {
                    return false;
                }

                if ($this->isLikelyCjkNoise($text, $nameHits)) {
                    return false;
                }

                return true;
            }
        ));

        usort($filtered, static fn ($a, $b): int => $b->confidence <=> $a->confidence);

        return $filtered;
    }

    /**
     * @return array<int, string>
     */
    private function extractNameParts(string $fullName): array
    {
        $parts = preg_split('/\s+/u', mb_strtolower(trim($fullName))) ?: [];

        return array_values(array_filter(
            $parts,
            static fn (string $part): bool => mb_strlen($part) > 1
        ));
    }

    /**
     * @param  array<int, string>  $nameParts
     */
    private function countNameHits(string $text, array $nameParts): int
    {
        $hits = 0;

        foreach ($nameParts as $part) {
            if (str_contains($text, $part)) {
                $hits++;
            }
        }

        return $hits;
    }

    private function isLikelyCjkNoise(string $text, int $nameHits): bool
    {
        preg_match_all('/\p{Han}|\p{Hiragana}|\p{Katakana}/u', $text, $cjkMatches);
        $cjkCount = count($cjkMatches[0] ?? []);
        if ($cjkCount === 0) {
            return false;
        }

        $hasCyrillicOrLatin = preg_match('/[\p{Cyrillic}\p{Latin}]/u', $text) === 1;

        return $nameHits <= 1 && ($cjkCount >= 6 || !$hasCyrillicOrLatin);
    }
}
