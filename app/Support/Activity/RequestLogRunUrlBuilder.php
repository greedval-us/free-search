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
