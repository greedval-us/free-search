<?php

namespace App\Support;

final class PublicBlueskyAccount
{
    public static function normalize(mixed $input): ?string
    {
        if (! is_string($input)) {
            return null;
        }
        $actor = trim($input);
        if (str_starts_with(strtolower($actor), 'bsky.app/')) {
            $actor = 'https://'.$actor;
        }
        if (str_contains($actor, '://')) {
            $parts = parse_url($actor);
            if ($parts === false || strtolower($parts['scheme'] ?? '') !== 'https'
                || strtolower($parts['host'] ?? '') !== 'bsky.app'
                || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['port']) || isset($parts['query']) || isset($parts['fragment'])
                || preg_match('~^/profile/([^/]+)/?$~D', $parts['path'] ?? '', $match) !== 1) {
                return null;
            }
            $actor = rawurldecode($match[1]);
        }
        $actor = str_starts_with($actor, '@') ? substr($actor, 1) : $actor;
        if ($actor === '' || strlen($actor) > 255 || preg_match('/[\x00-\x20\x7f]/', $actor)) {
            return null;
        }
        if (str_starts_with($actor, 'did:plc:')) {
            return preg_match('/^did:plc:[a-z2-7]{24}$/D', $actor) === 1 ? $actor : null;
        }
        if (str_starts_with($actor, 'did:web:')) {
            return self::validHandle(substr($actor, 8)) ? $actor : null;
        }

        return self::validHandle($actor) ? strtolower($actor) : null;
    }

    private static function validHandle(string $handle): bool
    {
        if (strlen($handle) > 253 || preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/D', $handle) !== 1) {
            return false;
        }
        $segments = explode('.', strtolower($handle));

        return ! in_array(end($segments), ['alt', 'arpa', 'example', 'internal', 'invalid', 'local', 'localhost', 'onion', 'test'], true);
    }
}
