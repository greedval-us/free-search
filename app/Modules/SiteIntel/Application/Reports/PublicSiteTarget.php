<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Modules\SiteIntel\Support\DomainNormalizer;

/** Syntax validation only; the target guard validates current DNS during every check. */
final class PublicSiteTarget
{
    public static function normalize(mixed $input): ?string
    {
        if (! is_string($input)) {
            return null;
        }
        $input = trim($input);
        if ($input === '' || strlen($input) > 512 || preg_match('/[\x00-\x20\x7f\\\\]/', $input)) {
            return null;
        }
        $candidate = str_contains($input, '://') ? $input : 'https://'.$input;
        $parts = parse_url($candidate);
        if (! is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! in_array($scheme, ['https', 'http'], true) || str_ends_with($host, '.')
            || DomainNormalizer::normalizeDomain($host) === null
            || preg_match('/\.(?:local|localhost|internal|intranet|lan|home|test|invalid|example)$/D', $host)
            || isset($parts['port'])) {
            return null;
        }
        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';
        $normalized = $scheme.'://'.$host.($path === '' ? '/' : $path).$query;

        return strlen($normalized) <= 512 ? $normalized : null;
    }
}
