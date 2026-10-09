<?php

namespace App\Modules\YouTube\Analytics\Reports;

final class PublicYouTubeChannel
{
    public static function normalize(mixed $input): ?string
    {
        if (! is_string($input)) {
            return null;
        }
        $input = trim($input);
        if ($input === '' || preg_match('/\s/u', $input) === 1) {
            return null;
        }
        if (str_contains($input, '/')) {
            $url = preg_match('~^https?://~i', $input) === 1 ? $input : 'https://'.$input;
            $parts = parse_url($url);
            if ($parts === false || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                || ! in_array(strtolower($parts['host'] ?? ''), ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)
                || isset($parts['port']) || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['query']) || isset($parts['fragment'])) {
                return null;
            }
            $path = rtrim(rawurldecode($parts['path'] ?? ''), '/');
            if (preg_match('~^/channel/(UC[A-Za-z0-9_-]{22})$~D', $path, $matches) === 1) {
                return $matches[1];
            }
            if (! str_starts_with($path, '/@')) {
                return null;
            }
            $input = substr($path, 1);
        }
        if (preg_match('/^UC[A-Za-z0-9_-]{22}$/D', $input) === 1) {
            return $input;
        }
        $handle = str_starts_with($input, '@') ? substr($input, 1) : $input;
        if (preg_match('/^[\p{L}\p{N}_.-]{3,30}$/uD', $handle) !== 1) {
            return null;
        }

        return '@'.mb_strtolower($handle);
    }
}
