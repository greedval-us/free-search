<?php

namespace App\Modules\Username\Application\Support;

final class UsernameConfig
{
    /**
     * @param array<string, mixed> $osintConfig
     * @param array<string, mixed> $usernameConfig
     */
    public static function fromArrays(array $osintConfig, array $usernameConfig): self
    {
        $requestConfig = self::arrayAt($usernameConfig, ['request']);
        $httpConfig = self::arrayAt($osintConfig, ['http']);
        $cacheConfig = self::arrayAt($usernameConfig, ['cache']);
        $similarityConfig = self::arrayAt($usernameConfig, ['analytics', 'similarity']);

        return new self(
            httpConnectTimeoutSeconds: max(1, self::intValue($httpConfig, ['connect_timeout_seconds'], self::intValue($requestConfig, ['connect_timeout'], 6))),
            httpTimeoutSeconds: max(1, self::intValue($httpConfig, ['timeout_seconds'], self::intValue($requestConfig, ['timeout'], 8))),
            httpMaxRedirects: max(0, self::intValue($httpConfig, ['max_redirects'], self::intValue($requestConfig, ['max_redirects'], 5))),
            httpUserAgent: self::stringValue(
                $httpConfig,
                ['user_agent'],
                self::stringValue($requestConfig, ['user_agent'], 'Mozilla/5.0 (compatible; UraborosOSINT/1.0; +https://localhost)')
            ),
            httpAccept: self::stringValue($httpConfig, ['accept'], 'text/html,application/xhtml+xml'),
            searchCacheTtlSeconds: max(1, self::intValue($cacheConfig, ['search_ttl_seconds'], 300)),
            similarityCacheTtlSeconds: max(1, self::intValue($cacheConfig, ['similarity_ttl_seconds'], 300)),
            similarityMaxVariants: max(1, self::intValue($similarityConfig, ['max_variants'], 8)),
            similarityDeepCheckVariants: max(0, self::intValue($similarityConfig, ['deep_check_variants'], 3)),
            similarityPrioritySourceKeys: self::stringList($similarityConfig['priority_source_keys'] ?? []),
            similarityRules: self::arrayValue($similarityConfig, ['rules']),
            sourceDefinitions: self::arrayValue($usernameConfig, ['sources']),
            categoryMapBySourceKey: self::arrayValue($usernameConfig, ['taxonomy', 'categories_by_source_key']),
        );
    }

    /**
     * @param array<int, string> $similarityPrioritySourceKeys
     * @param array<string, mixed> $similarityRules
     * @param array<int, mixed> $sourceDefinitions
     * @param array<string, mixed> $categoryMapBySourceKey
     */
    public function __construct(
        private readonly int $httpConnectTimeoutSeconds,
        private readonly int $httpTimeoutSeconds,
        private readonly int $httpMaxRedirects,
        private readonly string $httpUserAgent,
        private readonly string $httpAccept,
        private readonly int $searchCacheTtlSeconds,
        private readonly int $similarityCacheTtlSeconds,
        private readonly int $similarityMaxVariants,
        private readonly int $similarityDeepCheckVariants,
        private readonly array $similarityPrioritySourceKeys,
        private readonly array $similarityRules,
        private readonly array $sourceDefinitions,
        private readonly array $categoryMapBySourceKey,
    ) {
    }

    public function httpConnectTimeoutSeconds(): int
    {
        return $this->httpConnectTimeoutSeconds;
    }

    public function httpTimeoutSeconds(): int
    {
        return $this->httpTimeoutSeconds;
    }

    public function httpMaxRedirects(): int
    {
        return $this->httpMaxRedirects;
    }

    public function httpUserAgent(): string
    {
        return $this->httpUserAgent;
    }

    public function httpAccept(): string
    {
        return $this->httpAccept;
    }

    public function searchCacheTtlSeconds(): int
    {
        return $this->searchCacheTtlSeconds;
    }

    public function similarityCacheTtlSeconds(): int
    {
        return $this->similarityCacheTtlSeconds;
    }

    public function similarityMaxVariants(): int
    {
        return $this->similarityMaxVariants;
    }

    public function similarityDeepCheckVariants(): int
    {
        return $this->similarityDeepCheckVariants;
    }

    /**
     * @return array<int, string>
     */
    public function similarityPrioritySourceKeys(): array
    {
        return $this->similarityPrioritySourceKeys;
    }

    /**
     * @return array<string, mixed>
     */
    public function similarityRules(): array
    {
        return $this->similarityRules;
    }

    /**
     * @return array<int, mixed>
     */
    public function sourceDefinitions(): array
    {
        return $this->sourceDefinitions;
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryMapBySourceKey(): array
    {
        return $this->categoryMapBySourceKey;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private static function stringValue(array $config, array $path, string $default): string
    {
        $value = self::valueByPath($config, $path);

        return is_string($value) && trim($value) !== '' ? $value : $default;
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
     * @return array<string, mixed>
     */
    private static function arrayValue(array $config, array $path): array
    {
        $value = self::valueByPath($config, $path);

        return is_array($value) ? $value : [];
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $raw) {
            $item = trim((string) $raw);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     * @return array<string, mixed>
     */
    private static function arrayAt(array $config, array $path): array
    {
        $value = self::valueByPath($config, $path);

        return is_array($value) ? $value : [];
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
}
