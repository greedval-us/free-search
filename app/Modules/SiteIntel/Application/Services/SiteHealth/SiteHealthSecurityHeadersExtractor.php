<?php

namespace App\Modules\SiteIntel\Application\Services\SiteHealth;

final class SiteHealthSecurityHeadersExtractor
{
    private const REQUIRED_HEADERS = [
        'strict-transport-security',
        'content-security-policy',
        'x-frame-options',
        'x-content-type-options',
        'referrer-policy',
        'permissions-policy',
    ];

    /**
     * @param array<string, mixed> $headers
     * @return array<string, array<string, mixed>>
     */
    public function extract(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower((string) $key)] = $value;
        }

        $result = [];
        foreach (self::REQUIRED_HEADERS as $headerName) {
            $rawValue = $normalized[$headerName] ?? null;
            $value = null;

            if (is_array($rawValue)) {
                $value = count($rawValue) > 0 ? (string) $rawValue[0] : null;
            } elseif (is_string($rawValue)) {
                $value = $rawValue;
            }

            $result[$headerName] = [
                'present' => $value !== null && trim($value) !== '',
                'value' => $value,
            ];
        }

        return $result;
    }
}

