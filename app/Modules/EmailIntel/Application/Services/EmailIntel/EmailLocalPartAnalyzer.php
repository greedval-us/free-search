<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailLocalPartAnalyzer
{
    /**
     * @var array<int, string>
     */
    private const ROLE_PREFIXES = [
        'abuse',
        'admin',
        'billing',
        'contact',
        'help',
        'info',
        'legal',
        'marketing',
        'no-reply',
        'noreply',
        'postmaster',
        'sales',
        'security',
        'support',
        'webmaster',
    ];

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $localPart): array
    {
        $base = explode('+', $localPart, 2)[0];
        $tokens = array_values(array_filter(preg_split('/[._-]+/', $base) ?: []));
        $entropy = $this->entropy($base);

        return [
            'isRoleAccount' => in_array($base, self::ROLE_PREFIXES, true),
            'hasPlusAddressing' => str_contains($localPart, '+'),
            'hasYear' => preg_match('/(?:19|20)\d{2}/', $localPart) === 1,
            'hasSeparators' => preg_match('/[._-]/', $localPart) === 1,
            'tokens' => $tokens,
            'length' => mb_strlen($localPart),
            'entropy' => round($entropy, 2),
            'looksRandom' => mb_strlen($base) >= 12 && $entropy >= 3.2 && count($tokens) <= 1,
        ];
    }

    private function entropy(string $value): float
    {
        if ($value === '') {
            return 0.0;
        }

        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $length = count($chars);
        $counts = array_count_values($chars);
        $entropy = 0.0;

        foreach ($counts as $count) {
            $probability = $count / $length;
            $entropy -= $probability * log($probability, 2);
        }

        return $entropy;
    }
}
