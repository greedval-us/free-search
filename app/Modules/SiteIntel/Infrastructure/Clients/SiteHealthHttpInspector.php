<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Modules\SiteIntel\Application\Contracts\SiteHealthHttpInspectorInterface;
use App\Modules\SiteIntel\Application\Support\SiteIntelConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class SiteHealthHttpInspector implements SiteHealthHttpInspectorInterface
{
    public function __construct(private readonly SiteIntelConfig $config)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(string $url): array
    {
        $chain = [];
        $currentUrl = $url;
        $finalHeaders = [];
        $finalStatus = 0;

        for ($step = 0; $step <= $this->maxRedirects(); $step++) {
            $startedAt = microtime(true);

            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent(),
                    'Accept' => $this->acceptHeader(),
                ])
                    ->withOptions([
                        'allow_redirects' => false,
                        'verify' => $this->verifySsl(),
                    ])
                    ->timeout($this->timeoutSeconds())
                    ->get($currentUrl);
            } catch (ConnectionException $exception) {
                $chain[] = [
                    'url' => $currentUrl,
                    'status' => 0,
                    'location' => null,
                    'responseTimeMs' => (int) round((microtime(true) - $startedAt) * 1000),
                    'error' => $exception->getMessage(),
                ];

                return [
                    'chain' => $chain,
                    'finalUrl' => $currentUrl,
                    'finalStatus' => 0,
                    'totalRedirects' => max(0, count($chain) - 1),
                    'finalHeaders' => [],
                ];
            }

            $status = $response->status();
            $location = $response->header('Location');
            $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);
            $headers = $response->headers();

            $chain[] = [
                'url' => $currentUrl,
                'status' => $status,
                'location' => $location,
                'responseTimeMs' => $responseTimeMs,
                'error' => null,
            ];

            $finalHeaders = is_array($headers) ? $headers : [];
            $finalStatus = $status;

            if (!$this->isRedirectStatus($status) || !is_string($location) || $location === '') {
                break;
            }

            $resolved = $this->resolveRedirectUrl($currentUrl, $location);
            if ($resolved === null) {
                break;
            }

            $currentUrl = $resolved;
        }

        return [
            'chain' => $chain,
            'finalUrl' => $currentUrl,
            'finalStatus' => $finalStatus,
            'totalRedirects' => max(0, count($chain) - 1),
            'finalHeaders' => $finalHeaders,
        ];
    }

    private function isRedirectStatus(int $status): bool
    {
        return in_array($status, [301, 302, 303, 307, 308], true);
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

    private function userAgent(): string
    {
        return $this->config->siteHealthUserAgent();
    }

    private function acceptHeader(): string
    {
        return $this->config->httpAcceptHeader();
    }

    private function timeoutSeconds(): int
    {
        return $this->config->httpTimeoutSeconds();
    }

    private function maxRedirects(): int
    {
        return $this->config->httpMaxRedirects();
    }

    private function verifySsl(): bool
    {
        return $this->config->httpVerifySsl();
    }
}
