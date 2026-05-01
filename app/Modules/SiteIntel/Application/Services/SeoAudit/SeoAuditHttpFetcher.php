<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class SeoAuditHttpFetcher
{
    /**
     * @return array{url: string,status: int,headers: array<string, mixed>,body: string,responseTimeMs: int,error: string|null}
     */
    public function fetch(string $url): array
    {
        $startedAt = microtime(true);

        try {
            $response = Http::withHeaders([
                'User-Agent' => (string) config('osint.site_intel.http.user_agent', 'FreeSearch-SeoAudit/1.0'),
                'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
            ])
                ->withOptions([
                    'allow_redirects' => true,
                    'verify' => (bool) config('osint.site_intel.http.verify_ssl', false),
                ])
                ->timeout(max(1, (int) config('osint.site_intel.http.timeout_seconds', 10)))
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

