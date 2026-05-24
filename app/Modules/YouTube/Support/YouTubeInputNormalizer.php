<?php

namespace App\Modules\YouTube\Support;

final class YouTubeInputNormalizer
{
    public static function normalizeVideoId(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (!str_contains($value, '://') && !str_contains($value, 'youtube.com') && !str_contains($value, 'youtu.be')) {
            return $value;
        }

        $parts = parse_url($value);
        if (!is_array($parts)) {
            return $value;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        parse_str((string) ($parts['query'] ?? ''), $query);

        if (str_contains($host, 'youtu.be') && $path !== '') {
            return $path;
        }

        if (str_contains($host, 'youtube.com')) {
            $watchId = trim((string) ($query['v'] ?? ''));
            if ($watchId !== '') {
                return $watchId;
            }

            if (str_starts_with($path, 'shorts/')) {
                return trim(substr($path, strlen('shorts/')));
            }

            if (str_starts_with($path, 'embed/')) {
                return trim(substr($path, strlen('embed/')));
            }
        }

        return $value;
    }
}
