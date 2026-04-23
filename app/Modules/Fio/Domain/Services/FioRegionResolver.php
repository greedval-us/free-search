<?php

namespace App\Modules\Fio\Domain\Services;

final class FioRegionResolver
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $regionKeywords = [
        'cis' => ['russia', 'moscow', 'saint petersburg', 'ukraine', 'belarus', 'kazakhstan', 'россия', 'москва', 'санкт-петербург', 'украина', 'беларусь', 'казахстан'],
        'europe' => ['germany', 'france', 'italy', 'spain', 'poland', 'europe', 'berlin', 'paris', 'london', 'европа', 'германия', 'франция', 'испания', 'италия'],
        'americas' => ['usa', 'united states', 'canada', 'mexico', 'brazil', 'new york', 'california', 'america', 'сша', 'канада', 'мексика', 'бразилия'],
        'asia' => ['china', 'india', 'japan', 'korea', 'singapore', 'indonesia', 'thailand', 'asia', 'китай', 'индия', 'япония', 'корея', 'сингапур', 'азия'],
        'mena' => ['uae', 'dubai', 'saudi', 'qatar', 'egypt', 'turkey', 'mena', 'middle east', 'дубай', 'оаэ', 'саудовская аравия', 'катар', 'египет', 'турция'],
    ];

    public function resolve(?string $text): string
    {
        if (!is_string($text) || $text === '') {
            return 'unknown';
        }

        $haystack = mb_strtolower($text);

        foreach ($this->regionKeywords as $region => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, mb_strtolower($keyword))) {
                    return $region;
                }
            }
        }

        return 'unknown';
    }
}
