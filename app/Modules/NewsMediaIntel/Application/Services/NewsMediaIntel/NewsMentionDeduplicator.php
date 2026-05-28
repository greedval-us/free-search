<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

final class NewsMentionDeduplicator
{
    public function __construct(
        private readonly NewsMediaIntelConfig $config,
    ) {
    }

    /**
     * @param array<int, NewsMentionDTO> $mentions
     * @return array<int, NewsMentionDTO>
     */
    public function deduplicate(array $mentions): array
    {
        $linkMap = [];
        $contentMap = [];

        foreach ($mentions as $mention) {
            $linkKey = $this->normalizedLinkKey($mention->link);
            $contentKey = $this->normalizedContentKey($mention->title, $mention->snippet);

            if ($linkKey !== '' && isset($linkMap[$linkKey])) {
                $existing = $linkMap[$linkKey];
                if ($this->shouldReplace($existing, $mention)) {
                    $linkMap[$linkKey] = $mention;

                    if ($contentKey !== '') {
                        $contentMap[$contentKey] = $mention;
                    }
                }

                continue;
            }

            if ($contentKey !== '' && isset($contentMap[$contentKey])) {
                $existing = $contentMap[$contentKey];
                if ($this->shouldReplace($existing, $mention)) {
                    $contentMap[$contentKey] = $mention;

                    if ($linkKey !== '') {
                        $linkMap[$linkKey] = $mention;
                    }
                }

                continue;
            }

            if ($linkKey !== '') {
                $linkMap[$linkKey] = $mention;
            }

            if ($contentKey !== '') {
                $contentMap[$contentKey] = $mention;
            }
        }

        $merged = [];
        foreach (array_merge(array_values($linkMap), array_values($contentMap)) as $mention) {
            $key = spl_object_hash($mention);
            $merged[$key] = $mention;
        }

        return array_values($merged);
    }

    private function normalizedLinkKey(string $link): string
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

        $query = $this->normalizeQuery((string) ($parts['query'] ?? ''));

        return $host . $path . ($query === '' ? '' : '?' . $query);
    }

    private function normalizeQuery(string $query): string
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

    private function normalizedContentKey(string $title, string $snippet): string
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

    private function shouldReplace(NewsMentionDTO $existing, NewsMentionDTO $candidate): bool
    {
        $existingHasValidDate = $this->hasValidPublishedAt($existing->publishedAt);
        $candidateHasValidDate = $this->hasValidPublishedAt($candidate->publishedAt);

        if (!$existingHasValidDate && $candidateHasValidDate) {
            return true;
        }

        if ($existingHasValidDate !== $candidateHasValidDate) {
            return false;
        }

        return mb_strlen(trim($candidate->snippet)) > mb_strlen(trim($existing->snippet));
    }

    private function hasValidPublishedAt(string $value): bool
    {
        $raw = trim($value);
        if ($raw === '') {
            return false;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return false;
        }

        return date('Y-m-d', $timestamp) !== '1970-01-01';
    }
}
