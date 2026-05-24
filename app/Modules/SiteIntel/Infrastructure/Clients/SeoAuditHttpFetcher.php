<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Modules\SiteIntel\Application\Contracts\SeoAuditHttpFetcherInterface;
use App\Modules\SiteIntel\Application\Support\SiteIntelConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class SeoAuditHttpFetcher implements SeoAuditHttpFetcherInterface
{
    public function __construct(private readonly SiteIntelConfig $config)
    {
    }

    /**
     * @return array{url: string,status: int,headers: array<string, mixed>,body: string,responseTimeMs: int,error: string|null}
     */
    public function fetch(string $url): array
    {
        $startedAt = microtime(true);

        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->config->seoAuditUserAgent(),
                'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
            ])
                ->withOptions([
                    'allow_redirects' => true,
                    'verify' => $this->config->httpVerifySsl(),
                ])
                ->timeout($this->config->httpTimeoutSeconds())
                ->get($url);
        } catch (ConnectionException $exception) {
            return [
                'url' => $url,
                'status' => 0,
                'headers' => [],
                'body' => '',
                'responseTimeMs' => (int) round((microtime(true) - $startedAt) * 1000),
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'url' => (string) $response->effectiveUri(),
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->body(),
            'responseTimeMs' => (int) round((microtime(true) - $startedAt) * 1000),
            'error' => null,
        ];
    }
}
