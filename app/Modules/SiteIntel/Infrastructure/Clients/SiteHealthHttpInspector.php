<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Modules\SiteIntel\Application\Contracts\SiteHealthHttpInspectorInterface;
use App\Modules\SiteIntel\Application\Support\SiteIntelConfig;
use App\Modules\SiteIntel\Support\SiteIntelTargetGuard;
use App\Support\Observability\ExternalServiceLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class SiteHealthHttpInspector implements SiteHealthHttpInspectorInterface
{
    public function __construct(
        private readonly SiteIntelConfig $config,
        private readonly SiteIntelTargetGuard $targetGuard,
        private readonly SiteIntelHttpRequestOptions $requestOptions,
        private readonly SiteIntelRedirectUrlResolver $redirectUrlResolver,
        private readonly ExternalServiceLogger $externalServiceLogger,
    ) {}

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
            $target = $this->targetGuard->resolveSafeTarget($currentUrl);
            $startedAt = microtime(true);

            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent(),
                    'Accept' => $this->acceptHeader(),
                ])
                    ->withOptions($this->requestOptions->build($target, $this->verifySsl()))
                    ->timeout($this->timeoutSeconds())
                    ->get($target->url);
            } catch (ConnectionException $exception) {
                $this->externalServiceLogger->logConnectionFailure('site-intel', 'site-health-http', $exception, [
                    'url' => $currentUrl,
                ]);
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

            if (! $this->isRedirectStatus($status) || ! is_string($location) || $location === '') {
                break;
            }

            $resolved = $this->redirectUrlResolver->resolve($currentUrl, $location);
            if ($resolved === null) {
                $this->externalServiceLogger->logFallback('site-intel', 'site-health-http', 'redirect_resolution_failed', [
                    'url' => $currentUrl,
                    'location' => $location,
                ]);
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
