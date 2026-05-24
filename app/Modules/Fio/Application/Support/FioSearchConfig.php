<?php

namespace App\Modules\Fio\Application\Support;

final class FioSearchConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $networkDorkConfig = self::arrayValue($config, ['network_dork_search']);
        $legacyDorkConfig = self::arrayValue($config, ['dork_search']);

        return new self(
            qualifierLexicon: self::arrayValue($config, ['qualifier_lexicon']),
            sourceReliability: self::arrayValue($config, ['source_reliability']),
            networkDorkEngines: self::arrayList($networkDorkConfig['engines'] ?? []),
            networkDorkTemplates: self::stringList($networkDorkConfig['templates'] ?? []),
            networkDorkMaxQueries: max(1, self::intValue($networkDorkConfig['max_queries'] ?? null, 6)),
            dorkEnabled: self::boolValue($legacyDorkConfig['enabled'] ?? null, true),
            dorkMaxVariantsPerSource: max(1, self::intValue($legacyDorkConfig['max_variants_per_source'] ?? null, 4)),
            dorkTemplatesByScope: self::normalizeScopedTemplates(
                self::arrayList($legacyDorkConfig['templates'] ?? []),
                self::stringList($networkDorkConfig['templates'] ?? [])
            ),
        );
    }

    /**
     * @param array<string, mixed> $qualifierLexicon
     * @param array<string, mixed> $sourceReliability
     * @param array<int, mixed> $networkDorkEngines
     * @param array<int, string> $networkDorkTemplates
     * @param array<string, array<int, string>> $dorkTemplatesByScope
     */
    public function __construct(
        private readonly array $qualifierLexicon,
        private readonly array $sourceReliability,
        private readonly array $networkDorkEngines,
        private readonly array $networkDorkTemplates,
        private readonly int $networkDorkMaxQueries,
        private readonly bool $dorkEnabled,
        private readonly int $dorkMaxVariantsPerSource,
        private readonly array $dorkTemplatesByScope,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function qualifierLexicon(): array
    {
        return $this->qualifierLexicon;
    }

    /**
     * @return array<string, mixed>
     */
    public function sourceReliability(): array
    {
        return $this->sourceReliability;
    }

    /**
     * @return array<int, mixed>
     */
    public function networkDorkEngines(): array
    {
        return $this->networkDorkEngines;
    }

    /**
     * @return array<int, string>
     */
    public function networkDorkTemplates(): array
    {
        return $this->networkDorkTemplates;
    }

    public function networkDorkMaxQueries(): int
    {
        return $this->networkDorkMaxQueries;
    }

    public function dorkEnabled(): bool
    {
        return $this->dorkEnabled;
    }

    public function dorkMaxVariantsPerSource(): int
    {
        return $this->dorkMaxVariantsPerSource;
    }

    /**
     * @return array<int, string>
     */
    public function dorkTemplatesForScope(string $scope): array
    {
        $key = mb_strtolower(trim($scope));
        if ($key !== '' && array_key_exists($key, $this->dorkTemplatesByScope)) {
            return $this->dorkTemplatesByScope[$key];
        }

        return $this->dorkTemplatesByScope['web'] ?? [];
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
     */
    private static function intValue(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param mixed $value
     */
    private static function boolValue(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
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
     * @param mixed $value
     * @return array<int, mixed>
     */
    private static function arrayList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values($value);
    }

    /**
     * @param array<int, mixed> $templates
     * @param array<int, string> $fallback
     * @return array<string, array<int, string>>
     */
    private static function normalizeScopedTemplates(array $templates, array $fallback): array
    {
        if ($templates === []) {
            return [
                'web' => $fallback,
                'news' => $fallback,
                'social' => $fallback,
            ];
        }

        $isAssoc = array_keys($templates) !== range(0, count($templates) - 1);
        if (!$isAssoc) {
            $list = self::stringList($templates);

            return [
                'web' => $list,
                'news' => $list,
                'social' => $list,
            ];
        }

        $result = [];
        foreach ($templates as $scope => $items) {
            if (!is_string($scope)) {
                continue;
            }

            $key = mb_strtolower(trim($scope));
            if ($key === '') {
                continue;
            }

            $result[$key] = self::stringList($items);
        }

        foreach (['web', 'news', 'social'] as $scope) {
            if (!array_key_exists($scope, $result)) {
                $result[$scope] = $fallback;
            }
        }

        return $result;
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
