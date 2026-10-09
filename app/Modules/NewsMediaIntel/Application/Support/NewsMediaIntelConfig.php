<?php

namespace App\Modules\NewsMediaIntel\Application\Support;

final class NewsMediaIntelConfig
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            maxMentions: max(1, self::intValue($config, ['service', 'max_mentions'], 120)),
            searxngBaseUrl: trim(self::stringValue($config, ['searxng', 'base_url'], 'http://127.0.0.1:8088')),
            searxngLanguage: self::stringValue($config, ['searxng', 'language'], 'ru'),
            searxngEngines: self::stringListValue($config, ['searxng', 'engines']),
            searxngMaxPages: min(NewsSearchInputPolicy::MAX_PAGES, max(1, self::intValue($config, ['searxng', 'max_pages'], 3))),
            searxngTimeoutSeconds: min(20, max(1, self::intValue($config, ['searxng', 'timeout_seconds'], 10))),
            searxngRequestBudgetSeconds: min(25, max(1, self::intValue($config, ['searxng', 'request_budget_seconds'], 20))),
            searxngSafeSearch: min(NewsSearchInputPolicy::SAFE_SEARCH_STRICT, max(NewsSearchInputPolicy::SAFE_SEARCH_OFF,
                self::intValue($config, ['searxng', 'safe_search'], NewsSearchInputPolicy::SAFE_SEARCH_MODERATE))),
            searxngTimeRange: self::stringValue($config, ['searxng', 'time_range'], ''),
            sentimentPositiveWords: self::stringListValue($config, ['analysis', 'sentiment', 'positive_words']),
            sentimentNegativeWords: self::stringListValue($config, ['analysis', 'sentiment', 'negative_words']),
            topicStopWords: self::stringListValue($config, ['analysis', 'topics', 'stop_words']),
            topicMinWordLength: max(1, self::intValue($config, ['analysis', 'topics', 'min_word_length'], 4)),
            topicTopLimit: max(1, self::intValue($config, ['analysis', 'topics', 'top_limit'], 20)),
            dedupStripWww: self::boolValue($config, ['deduplication', 'strip_www'], true),
            dedupTrimTrailingSlash: self::boolValue($config, ['deduplication', 'trim_trailing_slash'], true),
            dedupQueryTrackers: self::stringListValue($config, ['deduplication', 'query_trackers']),
        );
    }

    public function __construct(
        private readonly int $maxMentions,
        private readonly string $searxngBaseUrl,
        private readonly string $searxngLanguage,
        /** @var array<int, string> */
        private readonly array $searxngEngines,
        private readonly int $searxngMaxPages,
        private readonly int $searxngTimeoutSeconds,
        private readonly int $searxngRequestBudgetSeconds,
        private readonly int $searxngSafeSearch,
        private readonly string $searxngTimeRange,
        /** @var array<int, string> */
        private readonly array $sentimentPositiveWords,
        /** @var array<int, string> */
        private readonly array $sentimentNegativeWords,
        /** @var array<int, string> */
        private readonly array $topicStopWords,
        private readonly int $topicMinWordLength,
        private readonly int $topicTopLimit,
        private readonly bool $dedupStripWww,
        private readonly bool $dedupTrimTrailingSlash,
        /** @var array<int, string> */
        private readonly array $dedupQueryTrackers,
    ) {}

    public function maxMentions(): int
    {
        return $this->maxMentions;
    }

    public function searxngBaseUrl(): string
    {
        return $this->searxngBaseUrl;
    }

    public function searxngLanguage(): string
    {
        return $this->searxngLanguage;
    }

    /** @return array<int, string> */
    public function searxngEngines(): array
    {
        return $this->searxngEngines;
    }

    public function searxngMaxPages(): int
    {
        return $this->searxngMaxPages;
    }

    public function searxngTimeoutSeconds(): int
    {
        return $this->searxngTimeoutSeconds;
    }

    public function searxngRequestBudgetSeconds(): int
    {
        return $this->searxngRequestBudgetSeconds;
    }

    public function searxngSafeSearch(): int
    {
        return $this->searxngSafeSearch;
    }

    public function searxngTimeRange(): string
    {
        return in_array($this->searxngTimeRange, NewsSearchInputPolicy::TIME_RANGES, true) ? $this->searxngTimeRange : '';
    }

    /** @return array<int, string> */
    public function sentimentPositiveWords(): array
    {
        return $this->sentimentPositiveWords;
    }

    /**
     * @return array<int, string>
     */
    public function sentimentNegativeWords(): array
    {
        return $this->sentimentNegativeWords;
    }

    /**
     * @return array<int, string>
     */
    public function topicStopWords(): array
    {
        return $this->topicStopWords;
    }

    public function topicMinWordLength(): int
    {
        return $this->topicMinWordLength;
    }

    public function topicTopLimit(): int
    {
        return $this->topicTopLimit;
    }

    public function dedupStripWww(): bool
    {
        return $this->dedupStripWww;
    }

    public function dedupTrimTrailingSlash(): bool
    {
        return $this->dedupTrimTrailingSlash;
    }

    /**
     * @return array<int, string>
     */
    public function dedupQueryTrackers(): array
    {
        return $this->dedupQueryTrackers;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $path
     */
    private static function stringValue(array $config, array $path, string $default): string
    {
        $value = self::valueByPath($config, $path);

        return is_string($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $path
     */
    private static function intValue(array $config, array $path, int $default): int
    {
        $value = self::valueByPath($config, $path);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $path
     */
    private static function boolValue(array $config, array $path, bool $default): bool
    {
        $value = self::valueByPath($config, $path);

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $path
     * @return array<int, string>
     */
    private static function stringListValue(array $config, array $path): array
    {
        $value = self::valueByPath($config, $path);
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $normalized = mb_strtolower(trim($item));
            if ($normalized === '') {
                continue;
            }

            $result[] = $normalized;
        }

        return array_values(array_unique($result));
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $path
     */
    private static function valueByPath(array $config, array $path): mixed
    {
        $cursor = $config;

        foreach ($path as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }
}
