<?php

namespace App\Modules\Fio\Domain\Services;

final class FioQualifierLexicon
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $groups;

    public function __construct()
    {
        $configGroups = config('fio.qualifier_lexicon', []);
        $this->groups = $this->normalizeGroups($configGroups);
    }

    /**
     * @return array<int, string>
     */
    public function expand(?string $qualifier): array
    {
        $normalized = $this->normalize($qualifier);
        if ($normalized === null) {
            return [];
        }

        $result = [$normalized];

        foreach ($this->groups as $groupTerms) {
            $normalizedGroupTerms = array_map([$this, 'normalizeTerm'], $groupTerms);

            if (in_array($normalized, $normalizedGroupTerms, true)) {
                foreach ($normalizedGroupTerms as $term) {
                    if (!in_array($term, $result, true)) {
                        $result[] = $term;
                    }
                }
            }
        }

        return $result;
    }

    public function normalize(?string $qualifier): ?string
    {
        if (!is_string($qualifier)) {
            return null;
        }

        $value = $this->normalizeTerm($qualifier);

        return $value !== '' ? $value : null;
    }

    public function isMatched(string $searchText, ?string $qualifier): bool
    {
        $terms = $this->expand($qualifier);
        if (count($terms) === 0) {
            return false;
        }

        $haystack = mb_strtolower($searchText);

        foreach ($terms as $term) {
            if (str_contains($haystack, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function queryTerms(?string $qualifier, int $limit = 4): array
    {
        $terms = $this->expand($qualifier);

        return array_slice($terms, 0, max(1, $limit));
    }

    /**
     * @param mixed $groups
     * @return array<string, array<int, string>>
     */
    private function normalizeGroups(mixed $groups): array
    {
        if (!is_array($groups)) {
            return [];
        }

        $normalized = [];

        foreach ($groups as $group => $terms) {
            if (!is_array($terms)) {
                continue;
            }

            $cleanTerms = [];
            foreach ($terms as $term) {
                if (!is_string($term)) {
                    continue;
                }

                $normalizedTerm = $this->normalizeTerm($term);
                if ($normalizedTerm === '') {
                    continue;
                }

                if (!in_array($normalizedTerm, $cleanTerms, true)) {
                    $cleanTerms[] = $normalizedTerm;
                }
            }

            if (count($cleanTerms) === 0) {
                continue;
            }

            $key = is_string($group) ? $group : (string) $group;
            $normalized[$key] = $cleanTerms;
        }

        return $normalized;
    }

    private function normalizeTerm(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return is_string($normalized) ? $normalized : '';
    }
}
