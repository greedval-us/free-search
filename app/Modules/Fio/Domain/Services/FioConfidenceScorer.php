<?php

namespace App\Modules\Fio\Domain\Services;

final class FioConfidenceScorer
{
    public function score(string $fullName, string $searchText): int
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
}
