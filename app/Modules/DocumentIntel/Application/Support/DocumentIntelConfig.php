<?php

namespace App\Modules\DocumentIntel\Application\Support;

final class DocumentIntelConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $http = self::arrayAt($config, ['http']);
        $discovery = self::arrayAt($config, ['discovery']);
        $extraction = self::arrayAt($config, ['extraction']);
        $risk = self::arrayAt($config, ['risk']);
        $riskThresholds = self::arrayAt($risk, ['thresholds']);
        $riskWeights = self::arrayAt($risk, ['weights']);

        return new self(
            httpUserAgent: self::stringValue($http, ['user_agent'], 'FreeSearch-DocumentIntel/1.0'),
            httpTimeoutSeconds: max(1, self::intValue($http, ['timeout_seconds'], 10)),
            discoveryExtensions: self::stringList($discovery['extensions'] ?? ['pdf', 'docx', 'xlsx', 'pptx']),
            discoveryMaxDocuments: max(1, self::intValue($discovery, ['max_documents'], 20)),
            discoveryMaxFileSizeBytes: max(1, self::intValue($discovery, ['max_file_size_bytes'], 5_000_000)),
            extractionMaxItemsPerType: max(1, self::intValue($extraction, ['max_items_per_type'], 15)),
            riskThresholdMedium: max(0, self::intValue($riskThresholds, ['medium'], 30)),
            riskThresholdHigh: max(0, self::intValue($riskThresholds, ['high'], 60)),
            riskWeights: $riskWeights,
        );
    }

    /**
     * @param array<int, string> $discoveryExtensions
     * @param array<string, mixed> $riskWeights
     */
    public function __construct(
        private readonly string $httpUserAgent,
        private readonly int $httpTimeoutSeconds,
        private readonly array $discoveryExtensions,
        private readonly int $discoveryMaxDocuments,
        private readonly int $discoveryMaxFileSizeBytes,
        private readonly int $extractionMaxItemsPerType,
        private readonly int $riskThresholdMedium,
        private readonly int $riskThresholdHigh,
        private readonly array $riskWeights,
    ) {
    }

    public function httpUserAgent(): string
    {
        return $this->httpUserAgent;
    }

    public function httpTimeoutSeconds(): int
    {
        return $this->httpTimeoutSeconds;
    }

    /**
     * @return array<int, string>
     */
    public function discoveryExtensions(): array
    {
        return $this->discoveryExtensions;
    }

    public function discoveryMaxDocuments(): int
    {
        return $this->discoveryMaxDocuments;
    }

    public function discoveryMaxFileSizeBytes(): int
    {
        return $this->discoveryMaxFileSizeBytes;
    }

    public function extractionMaxItemsPerType(): int
    {
        return $this->extractionMaxItemsPerType;
    }

    public function riskThresholdMedium(): int
    {
        return $this->riskThresholdMedium;
    }

    public function riskThresholdHigh(): int
    {
        return max($this->riskThresholdMedium, $this->riskThresholdHigh);
    }

    public function riskWeight(string $key, int $default): int
    {
        $value = $this->riskWeights[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
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
     * @param mixed $value
     * @return array<int, string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return ['pdf', 'docx', 'xlsx', 'pptx'];
        }

        $items = [];
        foreach ($value as $raw) {
            $item = strtolower(trim((string) $raw));
            if ($item !== '') {
                $items[] = $item;
            }
        }

        $items = array_values(array_unique($items));

        return $items !== [] ? $items : ['pdf', 'docx', 'xlsx', 'pptx'];
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
