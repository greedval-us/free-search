<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class AbstractFioHttpSearchProvider
{
    public function __construct(
        protected readonly FioQualifierLexicon $qualifierLexicon,
    ) {
    }

    protected function buildQuery(string $fullName, ?string $qualifier, int $termsLimit): string
    {
        $parts = ['"' . $fullName . '"'];

        $terms = $this->qualifierLexicon->queryTerms($qualifier, $termsLimit);

        if (count($terms) > 0) {
            $quoted = array_map(static fn (string $term): string => '"' . $term . '"', $terms);
            $parts[] = '(' . implode(' OR ', $quoted) . ')';
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function fetch(string $url, array $headers = []): string
    {
        $requestHeaders = array_merge(['User-Agent' => $this->userAgent()], $headers);

        try {
            $response = Http::withHeaders($requestHeaders)
                ->timeout($this->timeoutSeconds())
                ->retry($this->retryAttempts(), $this->retrySleepMilliseconds())
                ->get($url);
        } catch (ConnectionException) {
            throw new RuntimeException(__('Public search provider is temporarily unavailable. Try again.'));
        }

        if (!$response->ok()) {
            throw new RuntimeException(__('Unable to fetch public matches now. Try again later.'));
        }

        return (string) $response->body();
    }

    private function userAgent(): string
    {
        return (string) config('osint.fio.http.user_agent', 'FreeSearch-FIO/1.0');
    }

    private function timeoutSeconds(): int
    {
        return max(1, (int) config('osint.fio.http.timeout_seconds', 12));
    }

    private function retryAttempts(): int
    {
        return max(0, (int) config('osint.fio.http.retry_attempts', 1));
    }

    private function retrySleepMilliseconds(): int
    {
        return max(0, (int) config('osint.fio.http.retry_sleep_milliseconds', 250));
    }
}
