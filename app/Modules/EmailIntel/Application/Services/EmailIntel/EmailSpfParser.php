<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailSpfParser
{
    /**
     * @param array<int, string> $txtRecords
     * @return array<string, mixed>
     */
    public function parse(array $txtRecords): array
    {
        $record = $this->findRecord($txtRecords);
        if ($record === null) {
            return [
                'present' => false,
                'record' => null,
                'includes' => [],
                'ip4' => [],
                'ip6' => [],
                'mechanisms' => [],
                'all' => null,
                'strictness' => 'missing',
            ];
        }

        $tokens = preg_split('/\s+/', trim($record)) ?: [];

        return [
            'present' => true,
            'record' => $record,
            'includes' => $this->prefixedValues($tokens, 'include:'),
            'ip4' => $this->prefixedValues($tokens, 'ip4:'),
            'ip6' => $this->prefixedValues($tokens, 'ip6:'),
            'mechanisms' => array_values(array_filter($tokens, static fn (string $token): bool => $token !== 'v=spf1')),
            'all' => $this->allMechanism($tokens),
            'strictness' => $this->strictness($tokens),
        ];
    }

    /**
     * @param array<int, string> $txtRecords
     */
    private function findRecord(array $txtRecords): ?string
    {
        foreach ($txtRecords as $record) {
            if (str_starts_with(mb_strtolower($record), 'v=spf1')) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $tokens
     * @return array<int, string>
     */
    private function prefixedValues(array $tokens, string $prefix): array
    {
        $values = [];

        foreach ($tokens as $token) {
            if (str_starts_with($token, $prefix)) {
                $values[] = substr($token, strlen($prefix));
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * @param array<int, string> $tokens
     */
    private function allMechanism(array $tokens): ?string
    {
        foreach ($tokens as $token) {
            if (preg_match('/^[+\-~?]?all$/', $token) === 1) {
                return $token;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $tokens
     */
    private function strictness(array $tokens): string
    {
        return match ($this->allMechanism($tokens)) {
            '-all' => 'strict',
            '~all' => 'soft',
            '?all' => 'neutral',
            '+all' => 'open',
            default => 'unknown',
        };
    }
}
