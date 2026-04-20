<?php

namespace App\Modules\SiteIntel\Application\Services\DomainLite;

use Carbon\Carbon;

final class DomainLiteWhoisParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $server, string $response): array
    {
        $createdAt = $this->extractDate($response, [
            'Creation Date',
            'Created On',
            'Created',
            'Registered on',
            'Domain Registration Date',
        ]);
        $updatedAt = $this->extractDate($response, [
            'Updated Date',
            'Last Updated',
            'Modified',
        ]);
        $expiresAt = $this->extractDate($response, [
            'Registry Expiry Date',
            'Registrar Registration Expiration Date',
            'Expiry Date',
            'Expiration Date',
            'paid-till',
        ]);

        $registrar = $this->extractValue($response, ['Registrar', 'Sponsoring Registrar']);
        $country = $this->extractValue($response, ['Registrant Country', 'Country']);

        $sampleLines = preg_split('/\r\n|\r|\n/', $response) ?: [];
        $sampleLines = array_slice(
            array_values(array_filter($sampleLines, static fn (string $line): bool => trim($line) !== '')),
            0,
            30
        );

        return [
            'server' => $server,
            'available' => true,
            'createdAt' => $createdAt?->toIso8601String(),
            'updatedAt' => $updatedAt?->toIso8601String(),
            'expiresAt' => $expiresAt?->toIso8601String(),
            'registrar' => $registrar,
            'country' => $country,
            'sample' => implode("\n", $sampleLines),
        ];
    }

    /**
     * @param string[] $labels
     */
    private function extractDate(string $text, array $labels): ?Carbon
    {
        foreach ($labels as $label) {
            $value = $this->extractByLabel($text, $label);
            if ($value === null) {
                continue;
            }

            $timestamp = strtotime($value);
            if ($timestamp === false) {
                continue;
            }

            return Carbon::createFromTimestamp($timestamp);
        }

        return null;
    }

    /**
     * @param string[] $labels
     */
    private function extractValue(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $value = $this->extractByLabel($text, $label);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractByLabel(string $text, string $label): ?string
    {
        $pattern = '/^' . preg_quote($label, '/') . '\s*:\s*(.+)$/im';
        if (preg_match($pattern, $text, $matches) !== 1) {
            return null;
        }

        return trim((string) $matches[1]);
    }
}

