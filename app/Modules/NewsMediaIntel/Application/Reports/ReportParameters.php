<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use App\Support\Domains\PublicSiteTarget;

final readonly class ReportParameters
{
    public function __construct(private ReportConfig $reports, private NewsMediaIntelConfig $search) {}

    public function normalize(array $data): array
    {
        $queries = $this->strings($data['queries'] ?? null, $this->reports->maxQueries(), 180, 'invalid_queries');
        if ($queries === []) {
            throw new ReportException('invalid_queries');
        }
        foreach ($queries as $query) {
            if (preg_match('/(?:^|\s)[!:][^\s]+|[\p{Cc}]/u', $query) !== 0) {
                throw new ReportException('invalid_queries');
            }
        }
        $brand = $data['brand'] ?? '';
        if (! is_string($brand) || (trim($brand) !== '' && ! $this->validText(trim($brand), 80))) {
            throw new ReportException('invalid_options');
        }
        $brand = trim($brand);
        $competitors = $this->strings($data['competitors'] ?? [], 3, 80, 'invalid_options');
        if ($brand !== '' && in_array(mb_strtolower($brand), array_map(mb_strtolower(...), $competitors), true)) {
            throw new ReportException('invalid_options');
        }
        $domain = $data['domain'] ?? '';
        if (! is_string($domain) || mb_strlen($domain) > 253) {
            throw new ReportException('invalid_options');
        }
        $domain = trim($domain);
        if ($domain !== '') {
            $url = PublicSiteTarget::normalize($domain);
            if ($url === null) {
                throw new ReportException('invalid_options');
            }
            $domain = (string) parse_url($url, PHP_URL_HOST);
        }

        return ['queries' => $queries, 'brand' => $brand, 'competitors' => $competitors, 'domain' => $domain,
            'search_options' => $this->options($data['search_options'] ?? [])->toArray()];
    }

    public function options(mixed $data): NewsSearchOptionsDTO
    {
        if (! is_array($data)) {
            throw new ReportException('invalid_options');
        }
        $language = $data['language'] ?? $this->search->searxngLanguage();
        $timeRange = array_key_exists('timeRange', $data) ? ($data['timeRange'] ?? '') : $this->search->searxngTimeRange();
        $safeSearch = filter_var($data['safeSearch'] ?? $this->search->searxngSafeSearch(), FILTER_VALIDATE_INT);
        $maxPages = filter_var($data['maxPages'] ?? $this->search->searxngMaxPages(), FILTER_VALIDATE_INT);
        $engines = $data['engines'] ?? [];
        $available = array_merge(...array_values((array) config('osint.news_media_intel.searxng.available_engines', [])));
        if (! is_string($language) || ! in_array($language, config('osint.news_media_intel.searxng.languages', ['all', 'ru', 'en']), true)
            || ! in_array($timeRange, ['', 'day', 'week', 'month', 'year'], true)
            || ! in_array($safeSearch, [0, 1, 2], true)
            || $maxPages === false || $maxPages < 1 || $maxPages > $this->search->searxngMaxPages()
            || ! is_array($engines) || ! array_is_list($engines) || count($engines) > 8) {
            throw new ReportException('invalid_options');
        }
        foreach ($engines as $engine) {
            if (! is_string($engine) || ! in_array($engine, $available, true)) {
                throw new ReportException('invalid_options');
            }
        }
        if (count(array_unique($engines)) !== count($engines)) {
            throw new ReportException('invalid_options');
        }

        return new NewsSearchOptionsDTO(categories: ['general', 'news'], language: $language, timeRange: $timeRange,
            safeSearch: $safeSearch, engines: $engines, maxPages: $maxPages);
    }

    private function strings(mixed $values, int $limit, int $length, string $reason): array
    {
        if (! is_array($values) || ! array_is_list($values) || count($values) > $limit) {
            throw new ReportException($reason);
        }
        $result = $seen = [];
        foreach ($values as $value) {
            if (! is_string($value) || ! $this->validText(trim($value), $length)) {
                throw new ReportException($reason);
            }
            $value = trim($value);
            $key = mb_strtolower($value);
            if (isset($seen[$key])) {
                throw new ReportException($reason);
            }
            $seen[$key] = true;
            $result[] = $value;
        }

        return $result;
    }

    private function validText(string $value, int $length): bool
    {
        return mb_strlen($value) >= 2 && mb_strlen($value) <= $length && preg_match('/[\p{Cc}]/u', $value) === 0;
    }
}
