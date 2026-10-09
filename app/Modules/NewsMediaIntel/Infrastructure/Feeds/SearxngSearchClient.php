<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\NewsMediaIntel\Application\Contracts\SearxngSearchClientInterface;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionDeduplicator;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchResultDTO;
use App\Support\Observability\ExternalServiceLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class SearxngSearchClient implements SearxngSearchClientInterface
{
    public function __construct(
        private readonly NewsMediaIntelConfig $config,
        private readonly ExternalServiceLogger $externalServiceLogger,
        private readonly NewsMentionDeduplicator $deduplicator,
    ) {}

    public function search(string $query, ?NewsSearchOptionsDTO $options = null, ?float $deadline = null): NewsSearchResultDTO
    {
        if (preg_match('/(?:^|\s)[!:][^\s]+|[\p{Cc}]/u', $query) !== 0) {
            throw ValidationException::withMessages(['query' => __('news_media_intel.errors.query_routing')]);
        }
        $query = trim($query);
        $options = $this->options($options);
        $deadline = min($deadline ?? INF, microtime(true) + $this->config->searxngRequestBudgetSeconds());
        $coverage = [
            'pagesRequested' => 0, 'pagesLoaded' => 0, 'truncated' => false,
            'stopReason' => $query === '' ? 'empty_query' : 'page_limit',
            'unresponsiveEngines' => [], 'engines' => [], 'estimatedTotal' => null,
            'categories' => $options->categories, 'language' => $options->language,
            'timeRange' => $options->timeRange, 'requestedEngines' => $options->engines,
            'maxPages' => $options->maxPages,
        ];
        if ($query === '') {
            return new NewsSearchResultDTO([], $coverage);
        }
        $endpoint = $this->endpoint();
        $items = $seenPages = $suggestions = $corrections = $answers = $infoboxes = [];
        $position = 0;

        for ($page = 1; $page <= $options->maxPages; $page++) {
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                $coverage['stopReason'] = 'deadline';
                break;
            }
            $coverage['pagesRequested']++;
            try {
                $response = Http::asForm()->acceptJson()->withoutRedirecting()
                    ->connectTimeout(min(3, $remaining))
                    ->timeout(min($this->config->searxngTimeoutSeconds(), $remaining))
                    ->post($endpoint, $this->parameters($query, $page, $options));
            } catch (ConnectionException $exception) {
                $this->externalServiceLogger->logConnectionFailure('searxng', 'news_search', $exception, ['page' => $page]);
                if ($items === []) {
                    throw $this->unavailable();
                }
                $coverage['stopReason'] = 'connection_error';
                break;
            }
            if (! $response->successful()) {
                $this->externalServiceLogger->logHttpFailure('searxng', 'news_search', $response->status(), ['page' => $page]);
                if ($items === []) {
                    throw $this->unavailable();
                }
                $coverage['stopReason'] = 'http_error';
                break;
            }
            $payload = $response->json();
            if (! is_array($payload) || ! is_array($payload['results'] ?? null) || isset($payload['error'])) {
                $this->externalServiceLogger->logFallback('searxng', 'news_search', 'invalid_json_results', ['page' => $page]);
                if ($items === []) {
                    throw $this->unavailable();
                }
                $coverage['stopReason'] = 'invalid_response';
                break;
            }
            $coverage['pagesLoaded']++;
            $coverage['unresponsiveEngines'] = $this->uniqueRecords([
                ...$coverage['unresponsiveEngines'], ...$this->unresponsiveEngines($payload['unresponsive_engines'] ?? []),
            ], 32);
            if (is_int($payload['number_of_results'] ?? null) && $payload['number_of_results'] >= 0) {
                $coverage['estimatedTotal'] = max($coverage['estimatedTotal'] ?? 0, $payload['number_of_results']);
            }
            $suggestions = $this->uniqueTexts([...$suggestions, ...$this->texts($payload['suggestions'] ?? [])]);
            $corrections = $this->uniqueTexts([...$corrections, ...$this->texts($payload['corrections'] ?? [])]);
            $answers = array_slice($this->uniqueTexts([...$answers, ...$this->answers($payload['answers'] ?? [])]), 0, 10);
            $infoboxes = $this->uniqueRecords([...$infoboxes, ...$this->infoboxes($payload['infoboxes'] ?? [])], 5);
            if ($payload['results'] === []) {
                if ($coverage['unresponsiveEngines'] !== []) {
                    $this->externalServiceLogger->logFallback('searxng', 'news_search', 'engines_unavailable');
                    if ($items === []) {
                        throw $this->unavailable();
                    }
                    $coverage['stopReason'] = 'engines_unavailable';
                } else {
                    $coverage['stopReason'] = 'exhausted';
                }
                break;
            }
            $pageItems = [];
            foreach ($payload['results'] as $result) {
                // This is the observed merged-list ordinal, never an engine-specific rank.
                $mention = $this->mention($result, ++$position, $options->categories);
                if ($mention !== null) {
                    $pageItems[] = $mention;
                    $coverage['engines'] = array_values(array_unique([...$coverage['engines'], ...$mention->engines]));
                }
            }
            // Engines can ignore pageno. Exclude our changing ordinal from the fingerprint.
            $fingerprint = hash('sha256', serialize(array_map(static fn (NewsMentionDTO $mention): array => [
                $mention->link, $mention->title, $mention->snippet, $mention->publishedAt, $mention->engines, $mention->category,
            ], $pageItems)));
            if ($pageItems !== [] && isset($seenPages[$fingerprint])) {
                $coverage['stopReason'] = 'repeated_page';
                break;
            }
            $seenPages[$fingerprint] = true;
            // Distinct web URLs must reach visibility analysis with their own ordinal,
            // even when syndicated pages share the same title and snippet.
            $items = $this->deduplicator->deduplicate([...$items, ...$pageItems],
                byContent: ! in_array('general', $options->categories, true));
            if (count($items) >= $this->config->maxMentions()) {
                $coverage['stopReason'] = 'result_limit';
                break;
            }
            if ($pageItems === []) {
                $coverage['stopReason'] = 'invalid_results';
                break;
            }
        }
        sort($coverage['engines']);
        $coverage['truncated'] = $coverage['stopReason'] !== 'exhausted' || $coverage['unresponsiveEngines'] !== [];

        return new NewsSearchResultDTO(array_slice($items, 0, $this->config->maxMentions()), $coverage,
            $suggestions, $corrections, $answers, $infoboxes);
    }

    private function options(?NewsSearchOptionsDTO $options): NewsSearchOptionsDTO
    {
        $options ??= new NewsSearchOptionsDTO;
        $categories = $this->selectedValues($options->categories, ['news', 'general', 'all'], 'categories');
        if (in_array('all', $categories, true)) {
            $categories = ['news', 'general'];
        }
        $categories = $categories !== [] ? $categories : ['news'];
        $language = $options->language !== '' ? $options->language : $this->config->searxngLanguage();
        if (! preg_match('/^(?:[a-z]{2,3}(?:-[a-z]{2})?|auto|all)$/iD', $language)) {
            throw ValidationException::withMessages(['language' => __('news_media_intel.errors.invalid_options')]);
        }
        $timeRange = $options->timeRange ?? $this->config->searxngTimeRange();
        if (! in_array($timeRange, ['', 'day', 'week', 'month', 'year'], true)) {
            throw ValidationException::withMessages(['timeRange' => __('news_media_intel.errors.invalid_options')]);
        }
        $safeSearch = $options->safeSearch === -1 ? $this->config->searxngSafeSearch() : $options->safeSearch;
        if ($safeSearch < 0 || $safeSearch > 2) {
            throw ValidationException::withMessages(['safeSearch' => __('news_media_intel.errors.invalid_options')]);
        }
        $available = (array) config('osint.news_media_intel.searxng.available_engines', [
            'news' => ['google news', 'bing news', 'duckduckgo news', 'brave.news'],
            'general' => ['google', 'bing', 'duckduckgo', 'brave'],
        ]);
        $allowedEngines = [];
        foreach ($categories as $category) {
            $allowedEngines = [...$allowedEngines, ...(is_array($available[$category] ?? null) ? $available[$category] : [])];
        }
        $engines = $options->engines !== [] ? $options->engines : array_values(array_intersect($this->config->searxngEngines(), $allowedEngines));
        $engines = $this->selectedValues($engines, $allowedEngines, 'engines');

        return new NewsSearchOptionsDTO(categories: $categories, language: $language, timeRange: $timeRange,
            safeSearch: $safeSearch, engines: $engines,
            maxPages: $options->maxPages > 0 ? min(10, $options->maxPages) : $this->config->searxngMaxPages());
    }

    /** @param array<mixed> $values @param array<mixed> $allowed @return list<string> */
    private function selectedValues(array $values, array $allowed, string $field): array
    {
        $selected = [];
        foreach ($values as $value) {
            if (! is_string($value) || ! in_array(mb_strtolower(trim($value)), $allowed, true)) {
                throw ValidationException::withMessages([$field => __('news_media_intel.errors.invalid_options')]);
            }
            $selected[] = mb_strtolower(trim($value));
        }

        return array_values(array_unique($selected));
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
    private function parameters(string $query, int $page, NewsSearchOptionsDTO $options): array
    {
        $parameters = ['q' => $query, 'format' => 'json', 'language' => $options->language,
            'pageno' => $page, 'safesearch' => $options->safeSearch];
        if ($options->engines !== []) {
            // Supplying categories here would add all category engines to this selection.
            $parameters['engines'] = implode(',', $options->engines);
        } else {
            $parameters['categories'] = implode(',', $options->categories);
        }
        if ($options->timeRange !== '') {
            $parameters['time_range'] = $options->timeRange;
        }

        return $parameters;
    }

    /** @param list<string> $categories */
    private function mention(mixed $result, int $position, array $categories): ?NewsMentionDTO
    {
        if (! is_array($result)) {
            return null;
        }
        $title = $this->plainText($result['title'] ?? null, 1000);
        $url = is_string($result['url'] ?? null) ? $result['url'] : '';
        if ($title === '' || ! $this->isHttpUrl($url)) {
            return null;
        }
        $engines = $this->texts($result['engines'] ?? [], 32, 100);
        $engine = $this->plainText($result['engine'] ?? null, 100);
        if ($engine !== '') {
            $engines[] = $engine;
        }
        $engines = array_values(array_unique($engines));
        sort($engines);
        $category = in_array($result['category'] ?? '', ['news', 'general'], true)
            ? $result['category'] : (count($categories) === 1 ? $categories[0] : 'mixed');

        return new NewsMentionDTO(source: 'searxng', title: $title,
            snippet: $this->plainText($result['content'] ?? null, 4000), link: $url,
            publishedAt: $this->publicationDate($result['publishedDate'] ?? $result['published_at'] ?? null),
            engines: $engines, category: $category, position: $position,
            publisher: $this->plainText($result['publisher'] ?? $result['source'] ?? null, 200)
                ?: mb_strtolower((string) parse_url($url, PHP_URL_HOST)),
            categories: $category === 'mixed' ? [] : [$category]);
    }

    private function publicationDate(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }
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

    private function plainText(mixed $value, int $limit = 500): string
    {
        if (! is_string($value)) {
            return '';
        }
        $text = strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/[\p{Cc}\s]+/u', ' ', $text) ?? '';

        return mb_substr(trim($text), 0, $limit);
    }

    /** @return list<string> */
    private function texts(mixed $values, int $limit = 20, int $textLimit = 500): array
    {
        $texts = [];
        foreach (is_array($values) ? array_slice($values, 0, $limit) : [] as $value) {
            $text = $this->plainText($value, $textLimit);
            if ($text !== '') {
                $texts[] = $text;
            }
        }

        return array_values(array_unique($texts));
    }

    /** @param list<string> $texts @return list<string> */
    private function uniqueTexts(array $texts): array
    {
        return array_slice(array_values(array_unique($texts)), 0, 20);
    }

    /** @param array<mixed> $records @return list<array<string, mixed>> */
    private function uniqueRecords(array $records, int $limit): array
    {
        $unique = [];
        foreach ($records as $record) {
            $unique[serialize($record)] = $record;
        }

        return array_slice(array_values($unique), 0, $limit);
    }

    /** @return list<array{name:string,reason:string}> */
    private function unresponsiveEngines(mixed $values): array
    {
        $engines = [];
        foreach (is_array($values) ? array_slice($values, 0, 32) : [] as $value) {
            if (! is_array($value)) {
                continue;
            }
            $name = $this->plainText($value[0] ?? $value['name'] ?? null, 100);
            if ($name !== '') {
                $engines[] = ['name' => $name, 'reason' => $this->plainText($value[1] ?? $value['reason'] ?? null, 200)];
            }
        }

        return $engines;
    }

    /** @return list<string> */
    private function answers(mixed $values): array
    {
        $answers = [];
        foreach (is_array($values) ? array_slice($values, 0, 10) : [] as $value) {
            $text = $this->plainText(is_array($value) ? ($value['answer'] ?? $value['content'] ?? $value['title'] ?? null) : $value, 2000);
            if ($text !== '') {
                $answers[] = $text;
            }
        }

        return $answers;
    }

    /** @return list<array<string, mixed>> */
    private function infoboxes(mixed $values): array
    {
        $infoboxes = [];
        foreach (is_array($values) ? array_slice($values, 0, 5) : [] as $value) {
            if (! is_array($value)) {
                continue;
            }
            $title = $this->plainText($value['infobox'] ?? $value['title'] ?? null, 500);
            $content = $this->plainText($value['content'] ?? null, 2000);
            if ($title === '' && $content === '') {
                continue;
            }
            $urls = $attributes = [];
            foreach (is_array($value['urls'] ?? null) ? array_slice($value['urls'], 0, 5) : [] as $link) {
                if (is_array($link) && is_string($link['url'] ?? null) && $this->isHttpUrl($link['url'])) {
                    $urls[] = ['title' => $this->plainText($link['title'] ?? $link['name'] ?? null), 'url' => $link['url']];
                }
            }
            foreach (is_array($value['attributes'] ?? null) ? array_slice($value['attributes'], 0, 10) : [] as $attribute) {
                if (is_array($attribute)) {
                    $attributes[] = ['label' => $this->plainText($attribute['label'] ?? null), 'value' => $this->plainText($attribute['value'] ?? null)];
                }
            }
            $infoboxes[] = ['title' => $title, 'content' => $content, 'urls' => $urls, 'attributes' => $attributes,
                'engine' => $this->plainText($value['engine'] ?? null, 100)];
        }

        return $infoboxes;
    }

    private function isHttpUrl(string $url): bool
    {
        if (mb_strlen($url) > 4096 || preg_match('/[\p{Cc}\s\\\\]/u', $url) !== 0) {
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
