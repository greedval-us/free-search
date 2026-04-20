<?php

namespace App\Modules\SiteIntel\Support;

final class DomainNormalizer
{
    public static function normalizeDomain(string $value): ?string
    {
        $trimmed = trim(mb_strtolower($value));
        if ($trimmed === '') {
            return null;
        }

        if (str_contains($trimmed, '://')) {
            $parsedHost = parse_url($trimmed, PHP_URL_HOST);
            $trimmed = is_string($parsedHost) ? $parsedHost : $trimmed;
        }

        $trimmed = preg_replace('/:\d+$/', '', $trimmed) ?? $trimmed;
        $trimmed = trim($trimmed, ". \t\n\r\0\x0B");

        if ($trimmed === '' || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $trimmed)) {
            return null;
        }

        return $trimmed;
    }

    public static function normalizeUrl(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $candidate = str_contains($trimmed, '://') ? $trimmed : 'https://' . $trimmed;
        $parts = parse_url($candidate);

        if (!is_array($parts) || !isset($parts['host'])) {
            return null;
        }

        $host = self::normalizeDomain((string) $parts['host']);
        if ($host === null) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (!in_array($scheme, ['http', 'https'], true)) {
            $scheme = 'https';
        }

        $path = (string) ($parts['path'] ?? '/');
        if ($path === '') {
            $path = '/';
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        return sprintf('%s://%s%s%s', $scheme, $host, $path, $query);
    }
}

