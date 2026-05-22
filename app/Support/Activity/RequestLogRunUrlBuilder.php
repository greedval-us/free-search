<?php

namespace App\Support\Activity;

class RequestLogRunUrlBuilder
{
    /**
     * @param array<string, mixed> $payload
     */
    public function build(string $path, ?string $method, array $payload): ?string
    {
        if (strtoupper((string) $method) !== 'GET') {
            return null;
        }

        $normalizedPath = '/'.ltrim($path, '/');
        $moduleUrl = $this->buildModuleUrl($normalizedPath, $payload);

        if ($moduleUrl !== null) {
            return $moduleUrl;
        }

        $queryString = http_build_query($this->extractScalarPayload($payload));

        if ($queryString === '') {
            return $normalizedPath;
        }

        return $normalizedPath.'?'.$queryString;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildModuleUrl(string $path, array $payload): ?string
    {
        if ($path === '/username/search') {
            return $this->buildUrl('/username', [
                'tab' => 'search',
                'username' => $this->readString($payload, ['username']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/username/report') {
            return $this->buildUrl('/username', [
                'tab' => 'analytics',
                'username' => $this->readString($payload, ['username']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/fio/lookup') {
            return $this->buildUrl('/fio', [
                'full_name' => $this->readString($payload, ['full_name', 'fullName']),
                'qualifier' => $this->readString($payload, ['qualifier']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/site-intel/site-health') {
            return $this->buildUrl('/site-intel', [
                'tab' => 'siteHealth',
                'target' => $this->readString($payload, ['target']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/site-intel/domain-lite') {
            return $this->buildUrl('/site-intel', [
                'tab' => 'domainLite',
                'domain' => $this->readString($payload, ['domain']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/site-intel/analytics' || $path === '/site-intel/report') {
            return $this->buildUrl('/site-intel', [
                'tab' => 'analytics',
                'target' => $this->readString($payload, ['target']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/email-intel/lookup') {
            return $this->buildUrl('/email-intel', [
                'tab' => 'search',
                'email' => $this->readString($payload, ['email']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/email-intel/bulk') {
            return $this->buildUrl('/email-intel', [
                'tab' => 'bulk',
                'emails' => $this->readString($payload, ['emails']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/email-intel/domain-posture') {
            return $this->buildUrl('/email-intel', [
                'tab' => 'domain',
                'domain' => $this->readString($payload, ['domain']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/email-intel/report') {
            return $this->buildUrl('/email-intel', [
                'tab' => 'analytics',
                'email' => $this->readString($payload, ['email']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/telegram/search/messages' || $path === '/telegram/search/comments') {
            return $this->buildUrl('/telegram', [
                'tab' => 'search',
                'chatUsername' => $this->readString($payload, ['chatUsername']),
                'q' => $this->readString($payload, ['q']),
                'fromUsername' => $this->readString($payload, ['fromUsername']),
                'dateFrom' => $this->readString($payload, ['dateFrom']),
                'dateTo' => $this->readString($payload, ['dateTo']),
                'limit' => $this->readIntString($payload, 'limit'),
                'autorun' => '1',
            ]);
        }

        if ($path === '/telegram/analytics/summary' || $path === '/telegram/analytics/report') {
            return $this->buildUrl('/telegram', [
                'tab' => 'analytics',
                'chatUsername' => $this->readString($payload, ['chatUsername']),
                'keyword' => $this->readString($payload, ['keyword']),
                'periodDays' => $this->readIntString($payload, 'periodDays'),
                'dateFrom' => $this->readString($payload, ['dateFrom']),
                'dateTo' => $this->readString($payload, ['dateTo']),
                'scorePriority' => $this->readString($payload, ['scorePriority']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/youtube/search/videos') {
            return $this->buildUrl('/youtube', [
                'tab' => 'search',
                'q' => $this->readString($payload, ['q']),
                'type' => $this->readString($payload, ['type']),
                'channelId' => $this->readString($payload, ['channelId']),
                'order' => $this->readString($payload, ['order']),
                'publishedAfter' => $this->readString($payload, ['publishedAfter']),
                'publishedBefore' => $this->readString($payload, ['publishedBefore']),
                'regionCode' => $this->readString($payload, ['regionCode']),
                'relevanceLanguage' => $this->readString($payload, ['relevanceLanguage']),
                'safeSearch' => $this->readString($payload, ['safeSearch']),
                'videoDuration' => $this->readString($payload, ['videoDuration']),
                'videoDefinition' => $this->readString($payload, ['videoDefinition']),
                'videoCaption' => $this->readString($payload, ['videoCaption']),
                'limit' => $this->readIntString($payload, 'limit'),
                'autorun' => '1',
            ]);
        }

        if ($path === '/youtube/analytics/summary') {
            return $this->buildUrl('/youtube', [
                'tab' => 'analytics',
                'mode' => $this->readString($payload, ['mode']),
                'videoId' => $this->readString($payload, ['videoId']),
                'channelId' => $this->readString($payload, ['channelId']),
                'limit' => $this->readIntString($payload, 'limit'),
                'autorun' => '1',
            ]);
        }

        if ($path === '/youtube/parser/comments') {
            return $this->buildUrl('/youtube', [
                'tab' => 'parser',
                'videoId' => $this->readString($payload, ['videoId']),
                'order' => $this->readString($payload, ['order']),
                'searchTerms' => $this->readString($payload, ['searchTerms']),
                'limit' => $this->readIntString($payload, 'limit'),
                'autorun' => '1',
            ]);
        }

        if ($path === '/news-media-intel/lookup') {
            return $this->buildUrl('/news-media-intel', [
                'query' => $this->readString($payload, ['query']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/domain-infra-intel/lookup') {
            return $this->buildUrl('/domain-infra-intel', [
                'domain' => $this->readString($payload, ['domain']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/document-intel/lookup') {
            return $this->buildUrl('/document-intel', [
                'query' => $this->readString($payload, ['query']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/company-intel/lookup') {
            return $this->buildUrl('/company-intel', [
                'query' => $this->readString($payload, ['query']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/shifr/hash') {
            return $this->buildUrl('/shifr', [
                'tab' => 'hash',
                'text' => $this->readString($payload, ['text']),
                'algorithm' => $this->readString($payload, ['algorithm']),
                'hmac_key' => $this->readString($payload, ['hmac_key']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/shifr/transform') {
            return $this->buildUrl('/shifr', [
                'tab' => 'transform',
                'text' => $this->readString($payload, ['text']),
                'operation' => $this->readString($payload, ['operation']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/shifr/ioc-extract') {
            return $this->buildUrl('/shifr', [
                'tab' => 'ioc',
                'text' => $this->readString($payload, ['text']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/shifr/jwt-inspect') {
            return $this->buildUrl('/shifr', [
                'tab' => 'jwt',
                'token' => $this->readString($payload, ['token']),
                'secret' => $this->readString($payload, ['secret']),
                'autorun' => '1',
            ]);
        }

        if ($path === '/shifr/classic') {
            return $this->buildUrl('/shifr', [
                'tab' => 'classic',
                'text' => $this->readString($payload, ['text']),
                'cipher' => $this->readString($payload, ['cipher']),
                'direction' => $this->readString($payload, ['direction']),
                'shift' => $this->readIntString($payload, 'shift'),
                'key' => $this->readString($payload, ['key']),
                'rails' => $this->readIntString($payload, 'rails'),
                'xor_key' => $this->readString($payload, ['xor_key']),
                'affine_a' => $this->readIntString($payload, 'affine_a'),
                'affine_b' => $this->readIntString($payload, 'affine_b'),
                'playfair_key' => $this->readString($payload, ['playfair_key']),
                'column_key' => $this->readString($payload, ['column_key']),
                'morse_separator' => $this->readString($payload, ['morse_separator']),
                'autorun' => '1',
            ]);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractScalarPayload(array $payload): array
    {
        $result = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $keys
     */
    private function readString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;

            if (!is_scalar($value)) {
                continue;
            }

            $normalized = trim((string) $value);

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readIntString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return $value;
        }

        return null;
    }

    /**
     * @param array<string, string|null> $params
     */
    private function buildUrl(string $basePath, array $params): string
    {
        $query = [];
        foreach ($params as $key => $value) {
            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            $query[$key] = $value;
        }

        $queryString = http_build_query($query);

        if ($queryString === '') {
            return $basePath;
        }

        return $basePath.'?'.$queryString;
    }
}
