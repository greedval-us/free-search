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

        return $this->buildModuleUrl($normalizedPath, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildModuleUrl(string $path, array $payload): ?string
    {
        /** @var array<string, array{base: string, params: array<string, mixed>}> $definitions */
        $definitions = config('activity.run_url_routes', []);
        $definition = $definitions[$path] ?? null;

        if (!is_array($definition)) {
            return null;
        }

        $resolved = [];
        foreach ($definition['params'] as $queryKey => $spec) {
            if (is_string($spec)) {
                $resolved[$queryKey] = $spec;

                continue;
            }

            if (array_key_exists('keys', $spec)) {
                $resolved[$queryKey] = $this->readString($payload, $spec['keys']);

                continue;
            }

            if (array_key_exists('int', $spec)) {
                $resolved[$queryKey] = $this->readIntString($payload, $spec['int']);
            }
        }

        return $this->buildUrl($definition['base'], $resolved);
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
