<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailSimilarDomainGenerator
{
    /**
     * @return array<int, array{domain: string, reason: string}>
     */
    public function generate(string $domain): array
    {
        $parts = explode('.', $domain);
        if (count($parts) < 2) {
            return [];
        }

        $tld = array_pop($parts);
        $name = implode('.', $parts);
        $candidates = [];

        foreach (['com', 'net', 'org', 'io', 'co', 'ru'] as $candidateTld) {
            if ($candidateTld !== $tld) {
                $candidates[] = ['domain' => $name . '.' . $candidateTld, 'reason' => 'tld_variant'];
            }
        }

        $hyphenated = str_replace(['.', '_'], '-', $name);
        if ($hyphenated !== $name) {
            $candidates[] = ['domain' => $hyphenated . '.' . $tld, 'reason' => 'separator_variant'];
        }

        $compact = str_replace(['-', '.'], '', $name);
        if ($compact !== $name && $compact !== '') {
            $candidates[] = ['domain' => $compact . '.' . $tld, 'reason' => 'compact_variant'];
        }

        foreach (['0' => 'o', '1' => 'l', '3' => 'e'] as $from => $to) {
            if (str_contains($name, $to)) {
                $candidates[] = ['domain' => str_replace($to, $from, $name) . '.' . $tld, 'reason' => 'lookalike_variant'];
            }
        }

        return array_values(array_slice($this->unique($candidates, $domain), 0, 12));
    }

    /**
     * @param array<int, array{domain: string, reason: string}> $items
     * @return array<int, array{domain: string, reason: string}>
     */
    private function unique(array $items, string $original): array
    {
        $seen = [$original => true];
        $result = [];

        foreach ($items as $item) {
            if (isset($seen[$item['domain']])) {
                continue;
            }

            $seen[$item['domain']] = true;
            $result[] = $item;
        }

        return $result;
    }
}
