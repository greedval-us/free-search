<?php

namespace App\Modules\Fio\Domain\Services;

use Carbon\Carbon;

final class FioAgeAnalyzer
{
    public function extractAge(?string $text): ?int
    {
        if (!is_string($text) || $text === '') {
            return null;
        }

        if (preg_match('/\b([1-9][0-9])\s*(?:years?\s*old|years?|лет|года|г\.)\b/ui', $text, $matches) === 1) {
            $age = (int) ($matches[1] ?? 0);

            return $this->sanitizeAge($age);
        }

        if (preg_match('/\b(?:born|рожд[её]н(?:а)?|birth\s*year)\s*(?:in\s*)?(19[5-9][0-9]|20[0-1][0-9])\b/ui', $text, $matches) === 1) {
            $year = (int) ($matches[1] ?? 0);
            $age = Carbon::now()->year - $year;

            return $this->sanitizeAge($age);
        }

        return null;
    }

    public function resolveBucket(?int $age): string
    {
        if (!is_int($age)) {
            return 'unknown';
        }

        return match (true) {
            $age < 18 => 'under_18',
            $age <= 24 => '18_24',
            $age <= 34 => '25_34',
            $age <= 44 => '35_44',
            $age <= 54 => '45_54',
            default => '55_plus',
        };
    }

    private function sanitizeAge(int $age): ?int
    {
        return $age >= 0 && $age <= 100 ? $age : null;
    }
}
