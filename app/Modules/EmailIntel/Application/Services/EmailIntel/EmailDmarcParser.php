<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailDmarcParser
{
    /**
     * @param array<int, string> $records
     * @return array<string, mixed>
     */
    public function parse(array $records): array
    {
        $record = $records[0] ?? null;
        if ($record === null) {
            return [
                'present' => false,
                'record' => null,
                'policy' => null,
                'subdomainPolicy' => null,
                'pct' => null,
                'rua' => [],
                'ruf' => [],
                'adkim' => null,
                'aspf' => null,
                'strength' => 'missing',
            ];
        }

        $tags = $this->tags($record);
        $policy = $tags['p'] ?? null;

        return [
            'present' => true,
            'record' => $record,
            'policy' => $policy,
            'subdomainPolicy' => $tags['sp'] ?? null,
            'pct' => isset($tags['pct']) ? (int) $tags['pct'] : null,
            'rua' => $this->mailboxes($tags['rua'] ?? ''),
            'ruf' => $this->mailboxes($tags['ruf'] ?? ''),
            'adkim' => $tags['adkim'] ?? null,
            'aspf' => $tags['aspf'] ?? null,
            'strength' => match ($policy) {
                'reject' => 'strong',
                'quarantine' => 'moderate',
                'none' => 'monitoring',
                default => 'unknown',
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    private function tags(string $record): array
    {
        $tags = [];

        foreach (explode(';', $record) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key !== null && $value !== null && $key !== '') {
                $tags[mb_strtolower($key)] = trim($value);
            }
        }

        return $tags;
    }

    /**
     * @return array<int, string>
     */
    private function mailboxes(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
