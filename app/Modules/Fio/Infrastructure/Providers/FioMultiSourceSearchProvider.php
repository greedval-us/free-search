<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Application\Support\FioHttpConfig;
use App\Modules\Fio\Application\Support\FioSearchConfig;
use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\Contracts\FioSearchDiagnosticsAwareInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class FioMultiSourceSearchProvider implements FioPublicSearchProviderInterface, FioSearchDiagnosticsAwareInterface
{
    private const MAX_RESULTS = 260;
    private const MAX_RESULTS_PER_ENGINE = 80;

    /**
     * @var array{
     *     attemptedSources: array<int, array<string, mixed>>,
     *     sourceErrors: array<int, array<string, mixed>>
     * }
     */
    private array $lastDiagnostics = [
        'attemptedSources' => [],
        'sourceErrors' => [],
    ];

    public function __construct(
        private readonly FioQualifierLexicon $qualifierLexicon,
        private readonly FioHttpConfig $httpConfig,
        private readonly FioSearchConfig $searchConfig,
        private readonly FioMultiSourceResponseParser $responseParser,
        private readonly FioMultiSourceResultRanker $resultRanker,
    ) {
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName, ?string $qualifier = null): array
    {
        $this->lastDiagnostics = [
            'attemptedSources' => [],
            'sourceErrors' => [],
        ];

        $fullName = trim($fullName);
        if ($fullName === '') {
            return [];
        }

        $engines = $this->enginesConfig();
        $dorks = $this->buildDorks($fullName, $qualifier);
        $qualifierTerms = $this->qualifierLexicon->queryTerms($qualifier, 6);
        $collected = [];
        $lastError = null;

        foreach ($engines as $engine) {
            $sourceKey = (string) ($engine['source'] ?? 'unknown');
            $urlTemplate = (string) ($engine['url_template'] ?? '');
            if ($urlTemplate === '') {
                continue;
            }

            $startedAt = microtime(true);
            $engineCount = 0;
            $queryCount = 0;

            try {
                foreach ($dorks as $dorkQuery) {
                    if ($engineCount >= self::MAX_RESULTS_PER_ENGINE) {
                        break;
                    }

                    $queryCount++;
                    $url = str_replace('{query}', urlencode($dorkQuery), $urlTemplate);
                    $body = $this->fetch($url, is_array($engine['headers'] ?? null) ? $engine['headers'] : []);
                    $parsed = $this->responseParser->parseEngineResponse($body, $sourceKey);
                    $relevant = $this->resultRanker->filterRelevantEntries($parsed, $fullName, $qualifierTerms);

                    $engineCount += count($relevant);
                    $collected = [...$collected, ...$relevant];
                }

                $this->lastDiagnostics['attemptedSources'][] = [
                    'source' => $sourceKey,
                    'ok' => true,
                    'count' => $engineCount,
                    'queries' => $queryCount,
                    'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
                ];
            } catch (Throwable $exception) {
                $lastError = $exception;

                $duration = (int) round((microtime(true) - $startedAt) * 1000);
                $this->lastDiagnostics['attemptedSources'][] = [
                    'source' => $sourceKey,
                    'ok' => false,
                    'count' => 0,
                    'queries' => $queryCount,
                    'durationMs' => $duration,
                ];
                $this->lastDiagnostics['sourceErrors'][] = [
                    'source' => $sourceKey,
                    'message' => $exception->getMessage(),
                    'durationMs' => $duration,
                ];
            }
        }

        $deduplicated = $this->resultRanker->deduplicateAndRank($collected, $fullName, $qualifierTerms);
        if (count($deduplicated) > 0) {
            return array_slice($deduplicated, 0, self::MAX_RESULTS);
        }

        if ($lastError instanceof Throwable) {
            throw new RuntimeException($lastError->getMessage(), (int) $lastError->getCode(), $lastError);
        }

        return [];
    }

    public function diagnostics(): array
    {
        return $this->lastDiagnostics;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function enginesConfig(): array
    {
        $configured = $this->searchConfig->networkDorkEngines();
        if (is_array($configured) && $configured !== []) {
            return array_values(array_filter($configured, static fn ($engine): bool => is_array($engine)));
        }

        return [
            [
                'source' => 'duckduckgo',
                'url_template' => 'https://html.duckduckgo.com/html/?q={query}',
                'headers' => [],
            ],
            [
                'source' => 'bing',
                'url_template' => 'https://www.bing.com/search?format=rss&q={query}',
                'headers' => ['Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'],
            ],
            [
                'source' => 'googlenews',
                'url_template' => 'https://news.google.com/rss/search?q={query}&hl=ru&gl=RU&ceid=RU:ru',
                'headers' => ['Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'],
            ],
            [
                'source' => 'reddit',
                'url_template' => 'https://www.reddit.com/search.rss?q={query}',
                'headers' => ['Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'],
            ],
            [
                'source' => 'yahoo',
                'url_template' => 'https://search.yahoo.com/rss?p={query}',
                'headers' => ['Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function buildDorks(string $fullName, ?string $qualifier): array
    {
        $fullName = trim($fullName);
        $base = '"' . $fullName . '"';
        $qualifierTerms = $this->qualifierLexicon->queryTerms($qualifier, 6);
        $qualifierExpr = $qualifierTerms === []
            ? ''
            : '(' . implode(' OR ', array_map(static fn (string $term): string => '"' . $term . '"', $qualifierTerms)) . ')';

        $templates = $this->searchConfig->networkDorkTemplates();
        if (!is_array($templates) || $templates === []) {
            $templates = [
                '{name}',
                '{name} {qualifiers}',
                '{name} (intext:{name} OR intitle:{name}) {qualifiers}',
                '{name} (site:linkedin.com OR site:facebook.com OR site:vk.com OR site:ok.ru OR site:instagram.com OR site:x.com OR site:t.me) {qualifiers}',
                '{name} (site:gov OR site:edu OR site:mil) {qualifiers}',
                '{name} (resume OR biography OR profile OR contact) {qualifiers}',
            ];
        }

        $queries = [];
        foreach ($templates as $template) {
            if (!is_string($template) || trim($template) === '') {
                continue;
            }

            $query = strtr($template, [
                '{name}' => $base,
                '{qualifiers}' => $qualifierExpr,
            ]);

            $normalized = preg_replace('/\s+/u', ' ', trim(str_replace('()', '', $query)));
            if (!is_string($normalized) || $normalized === '') {
                continue;
            }

            $queries[] = $normalized;
        }

        $maxQueries = $this->searchConfig->networkDorkMaxQueries();

        return array_slice(array_values(array_unique($queries)), 0, $maxQueries);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function fetch(string $url, array $headers = []): string
    {
        $requestHeaders = array_merge([
            'User-Agent' => $this->httpConfig->multiSourceUserAgent(),
        ], $headers);

        try {
            $response = Http::withHeaders($requestHeaders)
                ->timeout($this->httpConfig->timeoutSeconds())
                ->retry(
                    $this->httpConfig->retryAttempts(),
                    $this->httpConfig->retrySleepMilliseconds()
                )
                ->get($url);
        } catch (ConnectionException) {
            throw new RuntimeException(__('Public search provider is temporarily unavailable. Try again.'));
        }

        if (!$response->ok()) {
            throw new RuntimeException(__('Unable to fetch public matches now. Try again later.'));
        }

        return (string) $response->body();
    }
}
