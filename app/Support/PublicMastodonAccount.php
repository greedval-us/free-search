<?php

namespace App\Support;

final class PublicMastodonAccount
{
    public static function normalize(mixed $input): ?string
    {
        if (! is_string($input) || strlen($input) > 255) {
            return null;
        }
        $value = trim($input);
        if (preg_match('~^https?://~i', $value) === 1) {
            $parts = parse_url($value);
            if (! is_array($parts) || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['port']) || isset($parts['query']) || isset($parts['fragment'])
                || preg_match('~^/(?:@|users/)([A-Za-z0-9_]{1,64})/?$~D', $parts['path'] ?? '', $matches) !== 1) {
                return null;
            }
            $value = $matches[1].'@'.($parts['host'] ?? '');
        } else {
            $value = preg_replace('/^@/', '', $value);
        }
        if (preg_match('/^([A-Za-z0-9_]{1,64})(?:@([^@\s]+))?$/D', $value, $matches) !== 1) {
            return null;
        }
        $username = strtolower($matches[1]);
        if (! isset($matches[2])) {
            return $username;
        }
        $host = strtolower($matches[2]);
        if (strlen($host) > 253 || filter_var($host, FILTER_VALIDATE_IP) !== false
            || preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $host) !== 1
            || preg_match('/\.(?:localhost|local|internal|test|invalid)$/D', $host) === 1) {
            return null;
        }

        return strlen($username.'@'.$host) <= 255 ? $username.'@'.$host : null;
    }
}
