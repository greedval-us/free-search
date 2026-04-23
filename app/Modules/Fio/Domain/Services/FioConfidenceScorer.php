<?php

namespace App\Modules\Fio\Domain\Services;

final class FioConfidenceScorer
{
    public function __construct(
        private readonly FioSourceReliabilityResolver $sourceReliabilityResolver,
        private readonly FioQualifierLexicon $qualifierLexicon,
    ) {
    }

    public function score(string $fullName, string $searchText, string $source, ?string $qualifier = null): int
    {
        $baseScore = $this->baseScore($fullName, $searchText);
        $weighted = (int) round($baseScore * $this->sourceReliabilityResolver->weight($source));
        $qualifierAdjusted = $this->applyQualifierAdjustment($weighted, $searchText, $qualifier);

        return max(0, min(100, $qualifierAdjusted));
    }

    public function sourceReliability(string $source): float
    {
        return $this->sourceReliabilityResolver->weight($source);
    }

    public function qualifierMatched(string $searchText, ?string $qualifier): bool
    {
        return $this->qualifierLexicon->isMatched($searchText, $qualifier);
    }

    private function baseScore(string $fullName, string $searchText): int
    {
        $target = mb_strtolower($fullName);
        $haystack = mb_strtolower($searchText);

        if ($target !== '' && str_contains($haystack, $target)) {
            return 90;
        }

        $parts = array_values(array_filter(explode(' ', $target)));
        if (count($parts) === 0) {
            return 0;
        }

        $hits = 0;
        foreach ($parts as $part) {
            if ($part !== '' && str_contains($haystack, $part)) {
                $hits++;
            }
        }

        $ratio = $hits / count($parts);

        if ($ratio >= 0.8) {
            return 75;
        }

        if ($ratio >= 0.5) {
            return 55;
        }

        return 35;
    }

    private function applyQualifierAdjustment(int $score, string $searchText, ?string $qualifier): int
    {
        if ($this->qualifierLexicon->normalize($qualifier) === null) {
            return $score;
        }

        if ($this->qualifierMatched($searchText, $qualifier)) {
            return $score + 12;
        }

        return $score - 4;
    }
}
