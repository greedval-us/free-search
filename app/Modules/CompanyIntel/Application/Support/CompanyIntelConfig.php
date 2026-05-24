<?php

namespace App\Modules\CompanyIntel\Application\Support;

final class CompanyIntelConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $risk = self::arrayAt($config, ['risk']);
        $links = self::arrayAt($config, ['links']);

        return new self(
            riskScoreForMedium: max(0, self::intValue($risk, ['score_for_medium'], 30)),
            riskScoreForHigh: max(0, self::intValue($risk, ['score_for_high'], 60)),
            riskWeights: self::arrayAt($risk, ['weights']),
            globalLinks: self::normalizeStringMap(self::arrayAt($links, ['global'])),
            domainLinks: self::normalizeStringMap(self::arrayAt($links, ['domain'])),
        );
    }

    /**
     * @param array<string, mixed> $riskWeights
     * @param array<string, string> $globalLinks
     * @param array<string, string> $domainLinks
     */
    public function __construct(
        private readonly int $riskScoreForMedium,
        private readonly int $riskScoreForHigh,
        private readonly array $riskWeights,
        private readonly array $globalLinks,
        private readonly array $domainLinks,
    ) {
    }

    public function riskScoreForMedium(): int
    {
        return $this->riskScoreForMedium;
    }

    public function riskScoreForHigh(): int
    {
        return max($this->riskScoreForMedium, $this->riskScoreForHigh);
    }

    public function riskWeight(string $key, int $default): int
    {
        $value = $this->riskWeights[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @return array<string, string>
     */
    public function globalLinkTemplates(): array
    {
        return $this->globalLinks;
    }

    /**
     * @return array<string, string>
     */
    public function domainLinkTemplates(): array
    {
        return $this->domainLinks;
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
    private static function intValue(array $config, array $path, int $default): int
    {
        $value = self::valueByPath($config, $path);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $map
     * @return array<string, string>
     */
    private static function normalizeStringMap(array $map): array
    {
        $normalized = [];
        foreach ($map as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }

            $key = trim($key);
            $value = trim($value);
            if ($key === '' || $value === '') {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
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
