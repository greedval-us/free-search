<?php

namespace App\Modules\SiteIntel\Application\Support;

final class SiteIntelConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $rawHttpUserAgent = self::stringAt($config, ['http', 'user_agent']);

        return new self(
            siteHealthUserAgent: $rawHttpUserAgent ?? 'FreeSearch-SiteHealth/1.0',
            seoAuditUserAgent: $rawHttpUserAgent ?? 'FreeSearch-SeoAudit/1.0',
            httpAcceptHeader: self::stringValue($config, ['http', 'accept'], '*/*'),
            httpTimeoutSeconds: max(1, self::intValue($config, ['http', 'timeout_seconds'], 10)),
            httpMaxRedirects: max(0, self::intValue($config, ['http', 'max_redirects'], 5)),
            httpVerifySsl: self::boolValue($config, ['http', 'verify_ssl'], false),
            whoisIanaServer: self::stringValue($config, ['whois', 'iana_server'], 'whois.iana.org'),
            whoisConnectTimeoutSeconds: max(1, self::intValue($config, ['whois', 'connect_timeout_seconds'], 8)),
            whoisReadTimeoutSeconds: max(1, self::intValue($config, ['whois', 'read_timeout_seconds'], 8)),
            whoisReadChunkSize: max(128, self::intValue($config, ['whois', 'read_chunk_size'], 2048)),
            whoisMaxResponseBytes: max(1024, self::intValue($config, ['whois', 'max_response_bytes'], 120000)),
        );
    }

    public function __construct(
        private readonly string $siteHealthUserAgent,
        private readonly string $seoAuditUserAgent,
        private readonly string $httpAcceptHeader,
        private readonly int $httpTimeoutSeconds,
        private readonly int $httpMaxRedirects,
        private readonly bool $httpVerifySsl,
        private readonly string $whoisIanaServer,
        private readonly int $whoisConnectTimeoutSeconds,
        private readonly int $whoisReadTimeoutSeconds,
        private readonly int $whoisReadChunkSize,
        private readonly int $whoisMaxResponseBytes,
    ) {
    }

    public function siteHealthUserAgent(): string
    {
        return $this->siteHealthUserAgent;
    }

    public function seoAuditUserAgent(): string
    {
        return $this->seoAuditUserAgent;
    }

    public function httpAcceptHeader(): string
    {
        return $this->httpAcceptHeader;
    }

    public function httpTimeoutSeconds(): int
    {
        return $this->httpTimeoutSeconds;
    }

    public function httpMaxRedirects(): int
    {
        return $this->httpMaxRedirects;
    }

    public function httpVerifySsl(): bool
    {
        return $this->httpVerifySsl;
    }

    public function whoisIanaServer(): string
    {
        return $this->whoisIanaServer;
    }

    public function whoisConnectTimeoutSeconds(): int
    {
        return $this->whoisConnectTimeoutSeconds;
    }

    public function whoisReadTimeoutSeconds(): int
    {
        return $this->whoisReadTimeoutSeconds;
    }

    public function whoisReadChunkSize(): int
    {
        return $this->whoisReadChunkSize;
    }

    public function whoisMaxResponseBytes(): int
    {
        return $this->whoisMaxResponseBytes;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private static function stringValue(array $config, array $path, string $default): string
    {
        return self::stringAt($config, $path) ?? $default;
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
    private static function boolValue(array $config, array $path, bool $default): bool
    {
        $value = self::valueByPath($config, $path);
        if (is_bool($value)) {
            return $value;
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private static function stringAt(array $config, array $path): ?string
    {
        $value = self::valueByPath($config, $path);

        return is_string($value) ? $value : null;
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

