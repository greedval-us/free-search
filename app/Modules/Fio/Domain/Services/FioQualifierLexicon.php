<?php

namespace App\Modules\Fio\Domain\Services;

final class FioQualifierLexicon
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $groups = [
        'руководитель' => ['руководитель', 'директор', 'начальник', 'управляющий', 'менеджер', 'manager', 'director', 'head', 'executive', 'ceo'],
        'военный' => ['военный', 'военнослужащий', 'армия', 'офицер', 'генерал', 'army', 'military', 'officer', 'veteran', 'defense'],
        'политик' => ['политик', 'депутат', 'министр', 'сенатор', 'politician', 'minister', 'senator', 'government', 'state official'],
        'предприниматель' => ['предприниматель', 'бизнесмен', 'основатель', 'инвестор', 'entrepreneur', 'businessman', 'founder', 'investor'],
        'юрист' => ['юрист', 'адвокат', 'прокурор', 'lawyer', 'attorney', 'legal counsel'],
        'врач' => ['врач', 'доктор', 'хирург', 'медик', 'doctor', 'physician', 'surgeon', 'medical'],
    ];

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

    private function normalizeTerm(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return is_string($normalized) ? $normalized : '';
    }
}
