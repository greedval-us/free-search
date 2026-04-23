<?php

namespace App\Modules\Dorks\Infrastructure\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class AbstractDorkHttpProvider
{
    /**
     * @param array<string, string> $headers
     */
    protected function fetch(string $url, array $headers = []): string
    {
        try {
            $response = Http::withHeaders(array_merge([
                'User-Agent' => $this->userAgent(),
                'Accept' => $this->acceptHeader(),
            ], $headers))
                ->connectTimeout($this->connectTimeoutSeconds())
                ->timeout($this->timeoutSeconds())
                ->get($url);
        } catch (ConnectionException) {
            throw new RuntimeException(__('Public search provider is temporarily unavailable. Try again.'));
        }

        if (!$response->ok()) {
            throw new RuntimeException(__('Unable to fetch public matches now. Try again later.'));
        }

        return (string) $response->body();
    }

    private function connectTimeoutSeconds(): int
    {
        return max(1, (int) config('osint.dorks.http.connect_timeout_seconds', 8));
    }

    private function timeoutSeconds(): int
    {
        return max(1, (int) config('osint.dorks.http.timeout_seconds', 12));
    }

    private function userAgent(): string
    {
        return (string) config('osint.dorks.http.user_agent', 'FreeSearch-Dorks/1.0');
    }

    private function acceptHeader(): string
    {
        return (string) config('osint.dorks.http.accept', 'text/html,application/xhtml+xml,application/xml');
    }
}

