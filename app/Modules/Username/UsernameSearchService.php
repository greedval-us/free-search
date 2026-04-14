<?php

namespace App\Modules\Username;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class UsernameSearchService
{
    /**
     * @return array<int, array{key: string, name: string, profileTemplate: string, notFoundMarkers?: array<int, string>}>
     */
    private function targets(): array
    {
        return [
            ['key' => 'github', 'name' => 'GitHub', 'profileTemplate' => 'https://github.com/%s'],
            ['key' => 'gitlab', 'name' => 'GitLab', 'profileTemplate' => 'https://gitlab.com/%s'],
            ['key' => 'reddit', 'name' => 'Reddit', 'profileTemplate' => 'https://www.reddit.com/user/%s'],
            ['key' => 'telegram', 'name' => 'Telegram', 'profileTemplate' => 'https://t.me/%s'],
            ['key' => 'devto', 'name' => 'DEV Community', 'profileTemplate' => 'https://dev.to/%s'],
            ['key' => 'huggingface', 'name' => 'Hugging Face', 'profileTemplate' => 'https://huggingface.co/%s'],
            ['key' => 'medium', 'name' => 'Medium', 'profileTemplate' => 'https://medium.com/@%s'],
            ['key' => 'codeberg', 'name' => 'Codeberg', 'profileTemplate' => 'https://codeberg.org/%s'],
            ['key' => 'npm', 'name' => 'npm', 'profileTemplate' => 'https://www.npmjs.com/~%s'],
            ['key' => 'dockerhub', 'name' => 'Docker Hub', 'profileTemplate' => 'https://hub.docker.com/u/%s'],
            ['key' => 'pypi', 'name' => 'PyPI', 'profileTemplate' => 'https://pypi.org/user/%s'],
            ['key' => 'keybase', 'name' => 'Keybase', 'profileTemplate' => 'https://keybase.io/%s'],
        ];
    }

    /**
     * @return array{username: string, checkedAt: string, summary: array{checked: int, found: int, notFound: int, unknown: int}, items: array<int, array<string, mixed>>}
     */
    public function search(string $username): array
    {
        $normalized = ltrim(trim($username), '@');
        $targets = $this->targets();

        $responses = Http::pool(fn (Pool $pool) => $this->buildPool($pool, $targets, $normalized));

        $items = [];
        $summary = [
            'checked' => count($targets),
            'found' => 0,
            'notFound' => 0,
            'unknown' => 0,
        ];

        foreach ($targets as $target) {
            $profileUrl = sprintf($target['profileTemplate'], $normalized);
            $response = $responses[$target['key']] ?? null;

            $result = $this->mapResponseToResult($response, $target, $profileUrl);
            $summary[$result['status'] === 'not_found' ? 'notFound' : $result['status']]++;

            $items[] = array_merge([
                'key' => $target['key'],
                'name' => $target['name'],
                'profileUrl' => $profileUrl,
            ], $result);
        }

        return [
            'username' => $normalized,
            'checkedAt' => now()->toIso8601String(),
            'summary' => $summary,
            'items' => $items,
        ];
    }

    /**
     * @param array<int, array{key: string, name: string, profileTemplate: string, notFoundMarkers?: array<int, string>}> $targets
     * @return array<string, Response>
     */
    private function buildPool(Pool $pool, array $targets, string $username): array
    {
        $requests = [];

        foreach ($targets as $target) {
            $profileUrl = sprintf($target['profileTemplate'], $username);

            $requests[$target['key']] = $pool
                ->as($target['key'])
                ->withHeaders($this->requestHeaders())
                ->connectTimeout(6)
                ->timeout(8)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                    ],
                ])
                ->get($profileUrl);
        }

        return $requests;
    }

    /**
     * @param array{key: string, name: string, profileTemplate: string, notFoundMarkers?: array<int, string>} $target
     * @return array{status: 'found'|'not_found'|'unknown', httpStatus: int|null, error: string|null}
     */
    private function mapResponseToResult(mixed $response, array $target, string $profileUrl): array
    {
        if (!($response instanceof Response)) {
            return [
                'status' => 'unknown',
                'httpStatus' => null,
                'error' => 'No response',
            ];
        }

        $statusCode = $response->status();

        if ($statusCode === 404 || $statusCode === 410) {
            return [
                'status' => 'not_found',
                'httpStatus' => $statusCode,
                'error' => null,
            ];
        }

        if ($response->successful()) {
            if ($this->looksLikeNotFound($response, $target, $profileUrl)) {
                return [
                    'status' => 'not_found',
                    'httpStatus' => $statusCode,
                    'error' => null,
                ];
            }

            return [
                'status' => 'found',
                'httpStatus' => $statusCode,
                'error' => null,
            ];
        }

        if (in_array($statusCode, [401, 403, 429], true)) {
            return [
                'status' => 'unknown',
                'httpStatus' => $statusCode,
                'error' => 'Access limited',
            ];
        }

        return [
            'status' => 'unknown',
            'httpStatus' => $statusCode,
            'error' => $response->reason(),
        ];
    }

    /**
     * @param array{key: string, name: string, profileTemplate: string, notFoundMarkers?: array<int, string>} $target
     */
    private function looksLikeNotFound(Response $response, array $target, string $profileUrl): bool
    {
        $markers = $target['notFoundMarkers'] ?? [];

        if ($markers === []) {
            return false;
        }

        $body = mb_strtolower($response->body());

        foreach ($markers as $marker) {
            if (str_contains($body, mb_strtolower($marker))) {
                return true;
            }
        }

        if ($response->effectiveUri() !== null) {
            $effective = rtrim((string) $response->effectiveUri(), '/');
            $expected = rtrim($profileUrl, '/');

            if ($effective !== '' && $expected !== '' && $effective !== $expected) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function requestHeaders(): array
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml',
            'User-Agent' => 'Mozilla/5.0 (compatible; UraborosOSINT/1.0; +https://localhost)',
        ];
    }
}
