<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;

final class FioMultiSourceResultRanker
{
    /**
     * @param  array<int, PublicSearchEntryDTO>  $entries
     * @param  array<int, string>  $qualifierTerms
     * @return array<int, PublicSearchEntryDTO>
     */
    public function filterRelevantEntries(array $entries, string $fullName, array $qualifierTerms): array
    {
        $nameParts = $this->nameParts($fullName);
        $requiredHits = count($nameParts) >= 3 ? 2 : max(1, count($nameParts));

        return array_values(array_filter($entries, function (PublicSearchEntryDTO $entry) use ($nameParts, $requiredHits, $qualifierTerms, $fullName): bool {
            $text = mb_strtolower(trim($entry->title . ' ' . $entry->snippet . ' ' . $entry->url));
            $hits = 0;
            foreach ($nameParts as $part) {
                if ($part !== '' && str_contains($text, $part)) {
                    $hits++;
                }
            }

            if ($hits < $requiredHits) {
                return false;
            }

            if ($this->isLikelyCjkNoise($text, $hits)) {
                return false;
            }

            if ($qualifierTerms !== []) {
                $hasQualifier = false;
                foreach ($qualifierTerms as $term) {
                    if (str_contains($text, mb_strtolower($term))) {
                        $hasQualifier = true;
                        break;
                    }
                }

                if (!$hasQualifier && !str_contains($text, mb_strtolower($fullName)) && !$this->isSocialDomain($entry->domain)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @param array<int, PublicSearchEntryDTO> $entries
     * @param array<int, string> $qualifierTerms
     * @return array<int, PublicSearchEntryDTO>
     */
    public function deduplicateAndRank(array $entries, string $fullName, array $qualifierTerms): array
    {
        $map = [];
        $nameParts = $this->nameParts($fullName);

        foreach ($entries as $entry) {
            $urlKey = mb_strtolower(trim($entry->url));
            $titleKey = mb_strtolower(trim($entry->title));
            $key = $urlKey !== '' ? $urlKey : $titleKey;

            if ($key === '' || array_key_exists($key, $map)) {
                continue;
            }

            $map[$key] = $entry;
        }

        $result = array_values($map);
        usort($result, function (PublicSearchEntryDTO $a, PublicSearchEntryDTO $b) use ($nameParts, $qualifierTerms): int {
            return $this->relevanceScore($b, $nameParts, $qualifierTerms) <=> $this->relevanceScore($a, $nameParts, $qualifierTerms);
        });

        return $result;
    }

    /**
     * @param  array<int, string>  $nameParts
     * @param  array<int, string>  $qualifierTerms
     */
    private function relevanceScore(PublicSearchEntryDTO $entry, array $nameParts, array $qualifierTerms): int
    {
        $text = mb_strtolower(trim($entry->title . ' ' . $entry->snippet . ' ' . $entry->url));
        $score = 0;

        foreach ($nameParts as $part) {
            if ($part !== '' && str_contains($text, $part)) {
                $score += 18;
            }
        }

        foreach ($qualifierTerms as $term) {
            if ($term !== '' && str_contains($text, mb_strtolower($term))) {
                $score += 8;
            }
        }

        if ($entry->source === 'googlenews') {
            $score += 4;
        }

        if ($this->isSocialDomain($entry->domain)) {
            $score += 12;
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function nameParts(string $fullName): array
    {
        $parts = preg_split('/\s+/u', mb_strtolower(trim($fullName))) ?: [];

        return array_values(array_filter($parts, static fn (string $part): bool => mb_strlen($part) > 1));
    }

    private function isSocialDomain(?string $domain): bool
    {
        $domain = mb_strtolower(trim((string) $domain));
        if ($domain === '') {
            return false;
        }

        foreach ([
            'linkedin.com', 'facebook.com', 'vk.com', 'ok.ru', 'instagram.com', 'x.com',
            'twitter.com', 't.me', 'telegram.me', 'youtube.com', 'tiktok.com', 'reddit.com',
        ] as $social) {
            if ($domain === $social || str_ends_with($domain, '.' . $social)) {
                return true;
            }
        }

        return false;
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
