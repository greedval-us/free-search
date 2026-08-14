<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Modules\SiteIntel\Application\Contracts\SeoAuditHttpFetcherInterface;
use App\Modules\SiteIntel\Application\Support\SiteIntelConfig;
use App\Modules\SiteIntel\Support\SiteIntelTargetGuard;
use App\Support\Observability\ExternalServiceLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class SeoAuditHttpFetcher implements SeoAuditHttpFetcherInterface
{
    public function __construct(
        private readonly SiteIntelConfig $config,
        private readonly SiteIntelTargetGuard $targetGuard,
        private readonly SiteIntelHttpRequestOptions $requestOptions,
        private readonly SiteIntelRedirectUrlResolver $redirectUrlResolver,
        private readonly ExternalServiceLogger $externalServiceLogger,
    ) {}

    /**
     * @return array{url: string,status: int,headers: array<string, mixed>,body: string,responseTimeMs: int,error: string|null}
     */
    public function fetch(string $url): array
    {
        $currentUrl = $url;
        $responseTimeMs = 0;

        for ($step = 0; $step <= $this->config->httpMaxRedirects(); $step++) {
            $target = $this->targetGuard->resolveSafeTarget($currentUrl);

            $startedAt = microtime(true);

            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->config->seoAuditUserAgent(),
                    'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
                ])
                    ->withOptions($this->requestOptions->build($target, $this->config->httpVerifySsl()))
                    ->timeout($this->config->httpTimeoutSeconds())
                    ->get($target->url);
            } catch (ConnectionException $exception) {
                $this->externalServiceLogger->logConnectionFailure('site-intel', 'seo-audit-fetch', $exception, [
                    'url' => $currentUrl,
                ]);

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

            if (! in_array($status, [301, 302, 303, 307, 308], true) || ! is_string($location) || $location === '') {
                return [
                    'url' => $currentUrl,
                    'status' => $status,
                    'headers' => $response->headers(),
                    'body' => $response->body(),
                    'responseTimeMs' => $responseTimeMs,
                    'error' => null,
                ];
            }

            $resolved = $this->redirectUrlResolver->resolve($currentUrl, $location);
            if ($resolved === null) {
                $this->externalServiceLogger->logFallback('site-intel', 'seo-audit-fetch', 'redirect_resolution_failed', [
                    'url' => $currentUrl,
                    'location' => $location,
                ]);
                break;
            }

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
}
