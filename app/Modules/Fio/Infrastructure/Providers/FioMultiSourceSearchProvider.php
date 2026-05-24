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
use SimpleXMLElement;
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
                    $parsed = $this->parseEngineResponse($body, $sourceKey);
                    $relevant = $this->filterRelevantEntries($parsed, $fullName, $qualifier);

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

        $deduplicated = $this->deduplicateAndRank($collected, $fullName, $qualifier);
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

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    private function parseEngineResponse(string $body, string $source): array
    {
        $trimmed = ltrim($body);
        if ($trimmed !== '' && $trimmed[0] === '<' && (str_contains($trimmed, '<rss') || str_contains($trimmed, '<feed'))) {
            return $this->parseRssOrAtom($trimmed, $source);
        }

        return $this->parseHtml($body, $source);
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    private function parseRssOrAtom(string $xml, string $source): array
    {
        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml);
        if (!$feed instanceof SimpleXMLElement) {
            return [];
        }

        $entries = [];
        $items = $feed->channel->item ?? [];
        if ($items !== []) {
            foreach ($items as $item) {
                $title = trim((string) ($item->title ?? ''));
                $url = $this->normalizeResultUrl((string) ($item->link ?? ''));
                $snippet = trim(strip_tags((string) ($item->description ?? '')));
                if ($url === '' || $title === '') {
                    continue;
                }

                $entries[] = new PublicSearchEntryDTO($title, $snippet, $url, $this->extractDomain($url), $source);
            }

            return $entries;
        }

        foreach (($feed->entry ?? []) as $entry) {
            $title = trim((string) ($entry->title ?? ''));
            $url = '';
            if (isset($entry->link)) {
                $attrs = $entry->link->attributes();
                $url = (string) ($attrs['href'] ?? '');
            }
            $url = $this->normalizeResultUrl($url);
            $snippet = trim(strip_tags((string) ($entry->summary ?? $entry->content ?? '')));
            if ($url === '' || $title === '') {
                continue;
            }

            $entries[] = new PublicSearchEntryDTO($title, $snippet, $url, $this->extractDomain($url), $source);
        }

        return $entries;
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    private function parseHtml(string $html, string $source): array
    {
        preg_match_all('/<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);
        $entries = [];

        foreach ($matches as $match) {
            $rawUrl = html_entity_decode((string) ($match[2] ?? ''), ENT_QUOTES | ENT_HTML5);
            $url = $this->normalizeResultUrl($rawUrl);
            $title = trim(strip_tags(html_entity_decode((string) ($match[3] ?? ''), ENT_QUOTES | ENT_HTML5)));

            if ($url === '' || $title === '' || !$this->isHttpUrl($url)) {
                continue;
            }

            $entries[] = new PublicSearchEntryDTO($title, '', $url, $this->extractDomain($url), $source);
        }

        return $entries;
    }

    /**
     * @param  array<int, PublicSearchEntryDTO>  $entries
     * @return array<int, PublicSearchEntryDTO>
     */
    private function filterRelevantEntries(array $entries, string $fullName, ?string $qualifier): array
    {
        $nameParts = $this->nameParts($fullName);
        $requiredHits = count($nameParts) >= 3 ? 2 : max(1, count($nameParts));
        $qualifierTerms = $this->qualifierLexicon->queryTerms($qualifier, 6);

        return array_values(array_filter($entries, function (PublicSearchEntryDTO $entry) use ($nameParts, $requiredHits, $qualifierTerms, $fullName): bool {
            $text = mb_strtolower(trim($entry->title . ' ' . $entry->snippet . ' ' . $entry->url));
            $hits = 0;
            foreach ($nameParts as $part) {
                if ($part !== '' && str_contains($text, $part)) {
                    $hits++;
                }
            }

            if ($hits < $requiredHits) {
                return false;
            }

            if ($this->isLikelyCjkNoise($text, $hits)) {
                return false;
            }

            if ($qualifierTerms !== []) {
                $hasQualifier = false;
                foreach ($qualifierTerms as $term) {
                    if (str_contains($text, mb_strtolower($term))) {
                        $hasQualifier = true;
                        break;
                    }
                }

                // Do not aggressively drop social-profile matches when qualifier terms are absent there.
                if (!$hasQualifier && !str_contains($text, mb_strtolower($fullName)) && !$this->isSocialDomain($entry->domain)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @param array<int, PublicSearchEntryDTO> $entries
     * @return array<int, PublicSearchEntryDTO>
     */
    private function deduplicateAndRank(array $entries, string $fullName, ?string $qualifier): array
    {
        $map = [];
        $nameParts = $this->nameParts($fullName);
        $qualifierTerms = $this->qualifierLexicon->queryTerms($qualifier, 6);

        foreach ($entries as $entry) {
            $urlKey = mb_strtolower(trim($entry->url));
            $titleKey = mb_strtolower(trim($entry->title));
            $key = $urlKey !== '' ? $urlKey : $titleKey;

            if ($key === '' || array_key_exists($key, $map)) {
                continue;
            }

            $map[$key] = $entry;
        }

        $result = array_values($map);
        usort($result, function (PublicSearchEntryDTO $a, PublicSearchEntryDTO $b) use ($nameParts, $qualifierTerms): int {
            return $this->relevanceScore($b, $nameParts, $qualifierTerms) <=> $this->relevanceScore($a, $nameParts, $qualifierTerms);
        });

        return $result;
    }

    /**
     * @param  array<int, string>  $nameParts
     * @param  array<int, string>  $qualifierTerms
     */
    private function relevanceScore(PublicSearchEntryDTO $entry, array $nameParts, array $qualifierTerms): int
    {
        $text = mb_strtolower(trim($entry->title . ' ' . $entry->snippet . ' ' . $entry->url));
        $score = 0;

        foreach ($nameParts as $part) {
            if ($part !== '' && str_contains($text, $part)) {
                $score += 18;
            }
        }

        foreach ($qualifierTerms as $term) {
            if ($term !== '' && str_contains($text, mb_strtolower($term))) {
                $score += 8;
            }
        }

        if ($entry->source === 'googlenews') {
            $score += 4;
        }

        if ($this->isSocialDomain($entry->domain)) {
            $score += 12;
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function nameParts(string $fullName): array
    {
        $parts = preg_split('/\s+/u', mb_strtolower(trim($fullName))) ?: [];

        return array_values(array_filter($parts, static fn (string $part): bool => mb_strlen($part) > 1));
    }

    private function normalizeResultUrl(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/l/?kh=') && str_contains($url, 'uddg=')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $decoded = urldecode((string) ($query['uddg'] ?? ''));
            if ($decoded !== '') {
                return $decoded;
            }
        }

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return '';
        }

        return $url;
    }

    private function isHttpUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    private function extractDomain(string $url): ?string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return $host !== '' ? $host : null;
    }

    private function isSocialDomain(?string $domain): bool
    {
        $domain = mb_strtolower(trim((string) $domain));
        if ($domain === '') {
            return false;
        }

        foreach ([
            'linkedin.com',
            'facebook.com',
            'vk.com',
            'ok.ru',
            'instagram.com',
            'x.com',
            'twitter.com',
            't.me',
            'telegram.me',
            'youtube.com',
            'tiktok.com',
            'reddit.com',
        ] as $social) {
            if ($domain === $social || str_ends_with($domain, '.' . $social)) {
                return true;
            }
        }

        return false;
    }

    private function isLikelyCjkNoise(string $text, int $nameHits): bool
    {
        preg_match_all('/\p{Han}|\p{Hiragana}|\p{Katakana}/u', $text, $cjkMatches);
        $cjkCount = count($cjkMatches[0] ?? []);
        if ($cjkCount === 0) {
            return false;
        }

        $hasCyrillicOrLatin = preg_match('/[\p{Cyrillic}\p{Latin}]/u', $text) === 1;

        return $nameHits <= 1 && ($cjkCount >= 6 || !$hasCyrillicOrLatin);
    }
}
