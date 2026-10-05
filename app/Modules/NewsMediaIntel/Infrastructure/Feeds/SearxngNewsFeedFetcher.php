<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionDeduplicator;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Support\Observability\ExternalServiceLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class SearxngNewsFeedFetcher implements NewsFeedFetcherInterface
{
    public function __construct(
        private readonly NewsMediaIntelConfig $config,
        private readonly ExternalServiceLogger $externalServiceLogger,
        private readonly NewsMentionDeduplicator $deduplicator,
    ) {}

    /** @return array<int, NewsMentionDTO> */
    public function fetchAll(string $query): array
    {
        if (trim($query) === '') {
            return [];
        }

        $endpoint = $this->endpoint();
        $deadline = microtime(true) + $this->config->searxngRequestBudgetSeconds();
        $items = [];
        $seenPages = [];

        for ($page = 1; $page <= $this->config->searxngMaxPages(); $page++) {
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                break;
            }

            try {
                $response = Http::asForm()->acceptJson()->withoutRedirecting()
                    ->connectTimeout(min(3, $remaining))
                    ->timeout(min($this->config->searxngTimeoutSeconds(), $remaining))
                    ->post($endpoint, $this->parameters($query, $page));
            } catch (ConnectionException $exception) {
                $this->externalServiceLogger->logConnectionFailure('searxng', 'news_search', $exception, ['page' => $page]);
                if ($items === []) {
                    throw $this->unavailable();
                }

                break;
            }

            if (! $response->successful()) {
                $this->externalServiceLogger->logHttpFailure('searxng', 'news_search', $response->status(), ['page' => $page]);
                if ($items === []) {
                    throw $this->unavailable();
                }

                break;
            }

            $payload = $response->json();
            if (! is_array($payload) || ! is_array($payload['results'] ?? null) || isset($payload['error'])) {
                $this->externalServiceLogger->logFallback('searxng', 'news_search', 'invalid_json_results', ['page' => $page]);
                if ($items === []) {
                    throw $this->unavailable();
                }

                break;
            }

            $results = $payload['results'];
            if ($results === []) {
                if ($items === [] && ! empty($payload['unresponsive_engines'])) {
                    $this->externalServiceLogger->logFallback('searxng', 'news_search', 'engines_unavailable');
                    throw $this->unavailable();
                }

                break;
            }

            $pageItems = [];
            foreach ($results as $result) {
                $mention = $this->mention($result);
                if ($mention !== null) {
                    $pageItems[] = $mention;
                }
            }

            // Some engines ignore pageno. Stop only on a repeated whole page,
            // while preserving richer duplicate results for the domain deduplicator.
            $fingerprint = hash('sha256', serialize(array_map(
                static fn (NewsMentionDTO $mention): array => $mention->toArray(), $pageItems
            )));
            if ($pageItems !== [] && isset($seenPages[$fingerprint])) {
                break;
            }
            $seenPages[$fingerprint] = true;

            $items = $this->deduplicator->deduplicate([...$items, ...$pageItems]);
            if (count($items) >= $this->config->maxMentions()) {
                break;
            }
        }

        return array_slice($items, 0, $this->config->maxMentions());
    }

    private function endpoint(): string
    {
        $url = $this->config->searxngBaseUrl();
        $parts = parse_url($url);
        if (! $this->isHttpUrl($url) || ! is_array($parts)
            || array_key_exists('query', $parts) || array_key_exists('fragment', $parts)) {
            $this->externalServiceLogger->logMisconfiguration('searxng', 'news_search');
            throw new ExternalServiceUnavailableException('errors.news_media_intel.configuration', 'news_search_configuration');
        }

        return rtrim($url, '/').'/search';
    }

    /** @return array<string, int|string> */
    private function parameters(string $query, int $page): array
    {
        $parameters = [
            'q' => $query,
            'format' => 'json',
            'categories' => 'news',
            'language' => $this->config->searxngLanguage(),
            'pageno' => $page,
            'safesearch' => $this->config->searxngSafeSearch(),
        ];
        if ($this->config->searxngEngines() !== []) {
            $parameters['engines'] = implode(',', $this->config->searxngEngines());
        }
        if ($this->config->searxngTimeRange() !== '') {
            $parameters['time_range'] = $this->config->searxngTimeRange();
        }

        return $parameters;
    }

    private function mention(mixed $result): ?NewsMentionDTO
    {
        if (! is_array($result)) {
            return null;
        }

        $title = $this->plainText($result['title'] ?? null);
        $url = is_string($result['url'] ?? null) ? $result['url'] : '';
        if ($title === '' || ! $this->isHttpUrl($url)) {
            return null;
        }

        return new NewsMentionDTO(
            source: 'searxng',
            title: mb_substr($title, 0, 1000),
            snippet: mb_substr($this->plainText($result['content'] ?? null), 0, 4000),
            link: $url,
            publishedAt: $this->publicationDate($result['publishedDate'] ?? $result['published_at'] ?? null),
        );
    }

    private function publicationDate(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        // SearXNG serializes datetimes as ISO; also accept full RFC feed dates.
        // PHP otherwise fills missing days or interprets a bare year as HHMM.
        $isIso = preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:?\d{2})?)?$/D', $value) === 1;
        $isRfc = preg_match('/^(?:[A-Za-z]{3}, )?\d{1,2} [A-Za-z]{3} \d{4} \d{2}:\d{2}:\d{2} (?:[+-]\d{4}|[A-Z]{1,5})$/D', $value) === 1;
        if (! $isIso && ! $isRfc) {
            return '';
        }

        $date = date_parse($value);
        if ($date['error_count'] !== 0 || $date['warning_count'] !== 0
            || ! is_int($date['year']) || ! is_int($date['month']) || ! is_int($date['day'])) {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? gmdate('c', $timestamp) : '';
    }

    private function plainText(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $text = strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function isHttpUrl(string $url): bool
    {
        if (preg_match('/[\p{Cc}\s\\\\]/u', $url) !== 0) {
            return false;
        }

        $parts = parse_url($url);

        return is_array($parts) && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && ($parts['host'] ?? '') !== '' && ! isset($parts['user']) && ! isset($parts['pass']);
    }

    private function unavailable(): ExternalServiceUnavailableException
    {
        return new ExternalServiceUnavailableException('errors.news_media_intel.unavailable', 'news_search_unavailable');
    }
}
