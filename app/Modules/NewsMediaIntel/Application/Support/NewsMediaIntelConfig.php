<?php

namespace App\Modules\NewsMediaIntel\Application\Support;

final class NewsMediaIntelConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            maxMentions: max(1, self::intValue($config, ['service', 'max_mentions'], 120)),
            perProviderLimit: max(1, self::intValue($config, ['fetcher', 'per_provider_limit'], 40)),
            providerOrder: self::resolveProviderOrder($config),
            rssTimeoutSeconds: max(1, self::intValue($config, ['rss', 'timeout_seconds'], 15)),
            rssAcceptHeader: self::stringValue(
                $config,
                ['rss', 'accept'],
                'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'
            ),
            bingRssUrlTemplate: self::stringValue(
                $config,
                ['rss', 'bing', 'url_template'],
                'https://www.bing.com/search?format=rss&q={query}'
            ),
            googleRssUrlTemplate: self::stringValue(
                $config,
                ['rss', 'google', 'url_template'],
                'https://news.google.com/rss/search?q={query}&hl={hl}&gl={gl}&ceid={ceid}'
            ),
            googleRssHl: self::stringValue($config, ['rss', 'google', 'hl'], 'ru'),
            googleRssGl: self::stringValue($config, ['rss', 'google', 'gl'], 'RU'),
            googleRssCeid: self::stringValue($config, ['rss', 'google', 'ceid'], 'RU:ru'),
            newsApiKey: trim(self::stringValue($config, ['newsapi', 'api_key'], '')),
            newsApiBaseUrl: self::stringValue($config, ['newsapi', 'base_url'], 'https://newsapi.org/v2/everything'),
            newsApiLanguage: self::stringValue($config, ['newsapi', 'language'], 'ru'),
            newsApiPageSize: max(1, self::intValue($config, ['newsapi', 'page_size'], 30)),
            newsApiTimeoutSeconds: max(1, self::intValue($config, ['newsapi', 'timeout_seconds'], 15)),
        );
    }

    public function __construct(
        private readonly int $maxMentions,
        private readonly int $perProviderLimit,
        /** @var array<int, string> */
        private readonly array $providerOrder,
        private readonly int $rssTimeoutSeconds,
        private readonly string $rssAcceptHeader,
        private readonly string $bingRssUrlTemplate,
        private readonly string $googleRssUrlTemplate,
        private readonly string $googleRssHl,
        private readonly string $googleRssGl,
        private readonly string $googleRssCeid,
        private readonly string $newsApiKey,
        private readonly string $newsApiBaseUrl,
        private readonly string $newsApiLanguage,
        private readonly int $newsApiPageSize,
        private readonly int $newsApiTimeoutSeconds,
    ) {
    }

    public function maxMentions(): int
    {
        return $this->maxMentions;
    }

    public function perProviderLimit(): int
    {
        return $this->perProviderLimit;
    }

    /**
     * @return array<int, string>
     */
    public function providerOrder(): array
    {
        return $this->providerOrder;
    }

    public function rssTimeoutSeconds(): int
    {
        return $this->rssTimeoutSeconds;
    }

    public function rssAcceptHeader(): string
    {
        return $this->rssAcceptHeader;
    }

    public function bingRssUrlTemplate(): string
    {
        return $this->bingRssUrlTemplate;
    }

    public function googleRssUrlTemplate(): string
    {
        return $this->googleRssUrlTemplate;
    }

    public function googleRssHl(): string
    {
        return $this->googleRssHl;
    }

    public function googleRssGl(): string
    {
        return $this->googleRssGl;
    }

    public function googleRssCeid(): string
    {
        return $this->googleRssCeid;
    }

    public function newsApiKey(): string
    {
        return $this->newsApiKey;
    }

    public function newsApiBaseUrl(): string
    {
        return $this->newsApiBaseUrl;
    }

    public function newsApiLanguage(): string
    {
        return $this->newsApiLanguage;
    }

    public function newsApiPageSize(): int
    {
        return $this->newsApiPageSize;
    }

    public function newsApiTimeoutSeconds(): int
    {
        return $this->newsApiTimeoutSeconds;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private static function stringValue(array $config, array $path, string $default): string
    {
        $value = self::valueByPath($config, $path);

        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private static function intValue(array $config, array $path, int $default): int
    {
        $value = self::valueByPath($config, $path);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private static function valueByPath(array $config, array $path): mixed
    {
        $cursor = $config;

        foreach ($path as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<int, string>
     */
    private static function resolveProviderOrder(array $config): array
    {
        $default = self::allowedProviderKeys();
        $raw = self::valueByPath($config, ['fetcher', 'provider_order']);
        if (!is_array($raw)) {
            return $default;
        }

        $allowed = array_fill_keys(self::allowedProviderKeys(), true);
        $items = [];
        foreach ($raw as $item) {
            if (!is_string($item)) {
                continue;
            }

            $key = strtolower(trim($item));
            if ($key === '') {
                continue;
            }

            if (!array_key_exists($key, $allowed)) {
                continue;
            }

            $items[] = $key;
        }

        $items = array_values(array_unique($items));

        return $items !== [] ? $items : $default;
    }

    /**
     * @return array<int, string>
     */
    private static function allowedProviderKeys(): array
    {
        return ['newsapi', 'googlenews', 'bing'];
    }
}
