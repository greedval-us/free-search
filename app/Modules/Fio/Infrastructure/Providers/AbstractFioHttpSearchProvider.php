<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
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
     * @return array<int, string>
     */
    protected function queryVariants(string $fullName, ?string $qualifier, int $termsLimit, string $scope = 'web'): array
    {
        $baseQuery = $this->buildQuery($fullName, $qualifier, $termsLimit);
        $variants = [$baseQuery];

        $dorksEnabled = (bool) config('fio.dork_search.enabled', true);
        if (!$dorksEnabled) {
            return $variants;
        }

        $templateMap = config('fio.dork_search.templates', []);
        if (!is_array($templateMap)) {
            return $variants;
        }

        $templates = $templateMap[$scope] ?? [];
        if (!is_array($templates) || $templates === []) {
            return $variants;
        }

        $qualifierTerms = $this->qualifierLexicon->queryTerms($qualifier, $termsLimit);
        $qualifierExpression = $qualifierTerms === []
            ? ''
            : '(' . implode(' OR ', array_map(static fn (string $term): string => '"' . $term . '"', $qualifierTerms)) . ')';

        foreach ($templates as $template) {
            if (!is_string($template) || trim($template) === '') {
                continue;
            }

            $query = strtr($template, [
                '{name}' => '"' . $fullName . '"',
                '{query}' => $baseQuery,
                '{qualifiers}' => $qualifierExpression,
            ]);

            $normalized = preg_replace('/\s+/u', ' ', trim(str_replace('()', '', $query)));
            if (!is_string($normalized) || $normalized === '') {
                continue;
            }

            $variants[] = $normalized;
        }

        $maxVariants = max(1, (int) config('fio.dork_search.max_variants_per_source', 4));

        return array_slice(array_values(array_unique($variants)), 0, $maxVariants);
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

    /**
     * @param  array<int, PublicSearchEntryDTO>  $entries
     * @return array<int, PublicSearchEntryDTO>
     */
    protected function deduplicateEntries(array $entries): array
    {
        $map = [];

        foreach ($entries as $entry) {
            $urlKey = mb_strtolower(trim($entry->url));
            $titleKey = mb_strtolower(trim($entry->title));
            $key = $urlKey !== '' ? $urlKey : $titleKey;

            if ($key === '' || array_key_exists($key, $map)) {
                continue;
            }

            $map[$key] = $entry;
        }

        return array_values($map);
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
