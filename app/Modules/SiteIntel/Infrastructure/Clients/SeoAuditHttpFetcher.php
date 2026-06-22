<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Modules\SiteIntel\Application\Contracts\SeoAuditHttpFetcherInterface;
use App\Modules\SiteIntel\Application\Support\SiteIntelConfig;
use App\Modules\SiteIntel\Support\SiteIntelTargetGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class SeoAuditHttpFetcher implements SeoAuditHttpFetcherInterface
{
    public function __construct(
        private readonly SiteIntelConfig $config,
        private readonly SiteIntelTargetGuard $targetGuard,
    ) {}

    /**
     * @return array{url: string,status: int,headers: array<string, mixed>,body: string,responseTimeMs: int,error: string|null}
     */
    public function fetch(string $url): array
    {
        $currentUrl = $url;
        $responseTimeMs = 0;

        for ($step = 0; $step <= $this->config->httpMaxRedirects(); $step++) {
            $this->targetGuard->assertSafeUrl($currentUrl);

            $startedAt = microtime(true);

            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->config->seoAuditUserAgent(),
                    'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
                ])
                    ->withOptions([
                        'allow_redirects' => false,
                        'verify' => $this->config->httpVerifySsl(),
                    ])
                    ->timeout($this->config->httpTimeoutSeconds())
                    ->get($currentUrl);
            } catch (ConnectionException $exception) {
                return [
                    'url' => $currentUrl,
                    'status' => 0,
                    'headers' => [],
                    'body' => '',
                    'responseTimeMs' => (int) round((microtime(true) - $startedAt) * 1000),
                    'error' => $exception->getMessage(),
                ];
            }

            $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);
            $status = $response->status();
            $location = $response->header('Location');

            if (!in_array($status, [301, 302, 303, 307, 308], true) || !is_string($location) || $location === '') {
                return [
                    'url' => $currentUrl,
                    'status' => $status,
                    'headers' => $response->headers(),
                    'body' => $response->body(),
                    'responseTimeMs' => $responseTimeMs,
                    'error' => null,
                ];
            }

            $resolved = $this->resolveRedirectUrl($currentUrl, $location);
            if ($resolved === null) {
                break;
            }

            $this->targetGuard->assertSafeUrl($resolved);
            $currentUrl = $resolved;
        }

        return [
            'url' => $currentUrl,
            'status' => 0,
            'headers' => [],
            'body' => '',
            'responseTimeMs' => $responseTimeMs,
            'error' => 'redirect_resolution_failed',
        ];
    }

    private function resolveRedirectUrl(string $currentUrl, string $location): ?string
    {
        if (str_starts_with($location, 'http://') || str_starts_with($location, 'https://')) {
            return $location;
        }

        $parts = parse_url($currentUrl);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = (string) $parts['scheme'];
        $host = (string) $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

        if (str_starts_with($location, '//')) {
            return $scheme . ':' . $location;
        }

        if (str_starts_with($location, '/')) {
            return sprintf('%s://%s%s%s', $scheme, $host, $port, $location);
        }

        $path = (string) ($parts['path'] ?? '/');
        $basePath = str_ends_with($path, '/') ? $path : dirname($path) . '/';
        if ($basePath === './') {
            $basePath = '/';
        }

        return sprintf('%s://%s%s%s%s', $scheme, $host, $port, $basePath, $location);
    }
}
