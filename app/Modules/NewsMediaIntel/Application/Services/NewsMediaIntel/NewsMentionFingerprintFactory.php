<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;

final class NewsMentionFingerprintFactory
{
    public function __construct(
        private readonly NewsMediaIntelConfig $config,
    ) {
    }

    public function linkKey(string $link): string
    {
        $raw = trim($link);
        if ($raw === '') {
            return '';
        }

        $parts = parse_url($raw);
        if (!is_array($parts)) {
            return mb_strtolower($raw);
        }

        $host = mb_strtolower((string) ($parts['host'] ?? ''));
        if ($this->config->dedupStripWww()) {
            $host = preg_replace('/^www\./i', '', $host) ?? $host;
        }

        $path = (string) ($parts['path'] ?? '');
        if ($this->config->dedupTrimTrailingSlash()) {
            $path = rtrim($path, '/');
        }
        $path = $path === '' ? '/' : $path;

        $query = $this->normalizedQuery((string) ($parts['query'] ?? ''));

        return $host . $path . ($query === '' ? '' : '?' . $query);
    }

    public function contentKey(string $title, string $snippet): string
    {
        $text = trim($title . ' ' . $snippet);
        if ($text === '') {
            return '';
        }

        $normalized = mb_strtolower($text);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        return $normalized === '' ? '' : $normalized;
    }

    private function normalizedQuery(string $query): string
    {
        if ($query === '') {
            return '';
        }

        parse_str($query, $params);

        if (!is_array($params) || $params === []) {
            return '';
        }

        $trackers = array_fill_keys($this->config->dedupQueryTrackers(), true);

        foreach (array_keys($params) as $key) {
            $normalized = mb_strtolower((string) $key);
            if (array_key_exists($normalized, $trackers)) {
                unset($params[$key]);
            }
        }

        if ($params === []) {
            return '';
        }

        ksort($params);

        return http_build_query($params);
    }
}

