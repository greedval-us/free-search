<?php

namespace App\Modules\Username;

use App\Modules\Username\Contracts\UsernameSourceCheckerInterface;
use App\Modules\Username\DTO\UsernameSearchQueryDTO;
use App\Modules\Username\DTO\UsernameSearchResultDTO;
use App\Modules\Username\DTO\UsernameSearchSummaryDTO;
use App\Modules\Username\DTO\UsernameSourceCheckResultDTO;
use Illuminate\Support\Facades\Cache;

final class UsernameSearchService
{
    private const CACHE_VERSION = 'v3';

    public function __construct(
        private readonly UsernameSourceCatalog $sourceCatalog,
        private readonly UsernameSourceCheckerInterface $sourceChecker,
    ) {
    }

    public function search(UsernameSearchQueryDTO $query): UsernameSearchResultDTO
    {
        $sources = $this->sourceCatalog->all();
        $username = mb_strtolower(trim($query->username));
        $cacheTtl = (int) config('username.cache.search_ttl_seconds', 300);

        /** @var array<int, array<string, mixed>> $cachedItems */
        $cachedItems = Cache::remember(
            $this->searchCacheKey($username),
            now()->addSeconds($cacheTtl),
            fn (): array => $this->serializeItems($this->sourceChecker->checkMany($sources, $username))
        );
        $items = $this->hydrateItems($cachedItems);

        $similarity = $this->buildSimilarity($username, $sources);
        $analytics = [
            'confidence' => $this->buildConfidenceSummary($items),
            'similarity' => $similarity,
            'graph' => $this->buildEntityGraph($username, $items),
        ];

        return new UsernameSearchResultDTO(
            username: $username,
            checkedAt: now()->toIso8601String(),
            summary: UsernameSearchSummaryDTO::fromResults($items),
            items: $items,
            analytics: $analytics,
        );
    }

    private function searchCacheKey(string $username): string
    {
        return sprintf('username:search:%s:%s', self::CACHE_VERSION, $username);
    }

    /**
     * @param array<int, UsernameSourceCheckResultDTO> $items
     * @return array<string, int|float>
     */
    private function buildConfidenceSummary(array $items): array
    {
        if ($items === []) {
            return [
                'average' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ];
        }

        $total = 0;
        $high = 0;
        $medium = 0;
        $low = 0;

        foreach ($items as $item) {
            $total += $item->confidence;

            if ($item->confidence >= 75) {
                $high++;
                continue;
            }

            if ($item->confidence >= 45) {
                $medium++;
                continue;
            }

            $low++;
        }

        return [
            'average' => round($total / count($items), 1),
            'high' => $high,
            'medium' => $medium,
            'low' => $low,
        ];
    }

    /**
     * @param array<int, \App\Modules\Username\DTO\UsernameSourceDTO> $sources
     * @return array<string, mixed>
     */
    private function buildSimilarity(string $username, array $sources): array
    {
        $variants = $this->generateVariants($username);

        if ($variants === []) {
            return ['variants' => []];
        }

        $priorityKeys = (array) config('username.analytics.similarity.priority_source_keys', []);
        $maxDeepCheck = (int) config('username.analytics.similarity.deep_check_variants', 3);
        $ttl = (int) config('username.cache.similarity_ttl_seconds', 300);

        $prioritySources = array_values(array_filter(
            $sources,
            static fn ($source): bool => in_array($source->key, $priorityKeys, true)
        ));

        $out = [];

        foreach ($variants as $index => $variantMeta) {
            $variant = $variantMeta['username'];
            $found = null;
            $checked = null;

            if ($index < $maxDeepCheck && $prioritySources !== []) {
                /** @var array<int, array<string, mixed>> $cachedChecks */
                $cachedChecks = Cache::remember(
                    sprintf('username:similarity:%s:%s', self::CACHE_VERSION, $variant),
                    now()->addSeconds($ttl),
                    fn (): array => $this->serializeItems($this->sourceChecker->checkMany($prioritySources, $variant))
                );
                $checks = $this->hydrateItems($cachedChecks);

                $found = 0;
                $checked = count($checks);

                foreach ($checks as $check) {
                    if ($check->status->value === 'found') {
                        $found++;
                    }
                }
            }

            $out[] = [
                'username' => $variant,
                'reason' => $variantMeta['reason'],
                'foundInPrioritySources' => $found,
                'checkedPrioritySources' => $checked,
            ];
        }

        return [
            'variants' => $out,
        ];
    }

    /**
     * @return array<int, array{username: string, reason: string}>
     */
    private function generateVariants(string $username): array
    {
        $base = trim($username);

        if ($base === '') {
            return [];
        }

        $base = mb_strtolower($base);
        $normalized = preg_replace('/[._-]+/u', '', $base) ?? $base;
        $segments = array_values(array_filter(preg_split('/[._-]+/u', $base) ?: []));
        $max = (int) config('username.analytics.similarity.max_variants', 8);
        $rules = $this->similarityRules();

        $variants = [];

        if ($segments !== []) {
            foreach ($rules['separators'] as $separator) {
                $this->addVariant($variants, implode($separator, $segments), 'separator', $base);
            }
        }

        $this->addVariant($variants, $normalized, 'normalized', $base);

        foreach ($rules['prefixes'] as $prefix) {
            $this->addVariant($variants, $prefix.'_'.$normalized, 'prefix', $base);
        }

        foreach ($rules['suffixes'] as $suffix) {
            foreach ($rules['separators'] as $separator) {
                $this->addVariant($variants, $normalized.$separator.$suffix, 'suffix', $base);
            }
        }

        foreach ($rules['numeric_suffixes'] as $numericSuffix) {
            foreach ($rules['separators'] as $separator) {
                $this->addVariant($variants, $normalized.$separator.$numericSuffix, 'numeric_suffix', $base);
            }
            $this->addVariant($variants, $normalized.$numericSuffix, 'numeric_suffix', $base);
        }

        foreach ($rules['leet_substitutions'] as $source => $target) {
            if (str_contains($normalized, $source)) {
                $this->addVariant(
                    $variants,
                    str_replace($source, $target, $normalized),
                    'leet',
                    $base
                );
            }
        }

        $variants = array_values($variants);

        return array_slice($variants, 0, max(1, $max));
    }

    /**
     * @return array{
     *   separators: array<int, string>,
     *   prefixes: array<int, string>,
     *   suffixes: array<int, string>,
     *   numeric_suffixes: array<int, string>,
     *   leet_substitutions: array<string, string>
     * }
     */
    private function similarityRules(): array
    {
        $rules = (array) config('username.analytics.similarity.rules', []);

        $separators = $this->normalizeStringList($rules['separators'] ?? ['_', '.', '-'], ['_', '.', '-']);
        $prefixes = $this->normalizeStringList($rules['prefixes'] ?? ['real', 'official', 'the'], ['real', 'official', 'the']);
        $suffixes = $this->normalizeStringList($rules['suffixes'] ?? ['official', 'dev', 'team', 'hq'], ['official', 'dev', 'team', 'hq']);
        $numericSuffixes = $this->normalizeStringList($rules['numeric_suffixes'] ?? ['1', '01', '24', '2026'], ['1', '01', '24', '2026']);

        $leet = [];
        $rawLeet = $rules['leet_substitutions'] ?? [];

        if (is_array($rawLeet)) {
            foreach ($rawLeet as $source => $target) {
                $sourceKey = mb_strtolower(trim((string) $source));
                $targetValue = trim((string) $target);

                if ($sourceKey !== '' && $targetValue !== '') {
                    $leet[$sourceKey] = $targetValue;
                }
            }
        }

        if ($leet === []) {
            $leet = ['a' => '4', 'e' => '3', 'i' => '1', 'o' => '0', 's' => '5'];
        }

        return [
            'separators' => $separators,
            'prefixes' => $prefixes,
            'suffixes' => $suffixes,
            'numeric_suffixes' => $numericSuffixes,
            'leet_substitutions' => $leet,
        ];
    }

    /**
     * @param array<string, array{username: string, reason: string}> $variants
     */
    private function addVariant(array &$variants, string $value, string $reason, string $base): void
    {
        $candidate = mb_strtolower(trim($value));

        if ($candidate === '' || $candidate === $base) {
            return;
        }

        if (!preg_match('/^[a-z0-9._-]{2,64}$/', $candidate)) {
            return;
        }

        $variants[$candidate] = [
            'username' => $candidate,
            'reason' => $reason,
        ];
    }

    /**
     * @param mixed $value
     * @param array<int, string> $fallback
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value, array $fallback): array
    {
        if (!is_array($value)) {
            return $fallback;
        }

        $items = [];

        foreach ($value as $rawItem) {
            $item = trim((string) $rawItem);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        $items = array_values(array_unique($items));

        return $items !== [] ? $items : $fallback;
    }

    /**
     * @param array<int, UsernameSourceCheckResultDTO> $items
     * @return array<string, mixed>
     */
    private function buildEntityGraph(string $username, array $items): array
    {
        $nodes = [
            [
                'id' => 'username:'.$username,
                'type' => 'username',
                'label' => $username,
            ],
        ];
        $edges = [];
        $regionSeen = [];

        foreach ($items as $item) {
            $platformNodeId = 'platform:'.$item->key;
            $regionNodeId = 'region:'.$item->regionGroup;

            $nodes[] = [
                'id' => $platformNodeId,
                'type' => 'platform',
                'label' => $item->name,
                'status' => $item->status->value,
                'confidence' => $item->confidence,
            ];

            if (!isset($regionSeen[$item->regionGroup])) {
                $nodes[] = [
                    'id' => $regionNodeId,
                    'type' => 'region',
                    'label' => $item->regionGroup,
                ];
                $regionSeen[$item->regionGroup] = true;
            }

            $edges[] = [
                'source' => 'username:'.$username,
                'target' => $platformNodeId,
                'kind' => 'presence',
                'status' => $item->status->value,
                'confidence' => $item->confidence,
            ];

            $edges[] = [
                'source' => $platformNodeId,
                'target' => $regionNodeId,
                'kind' => 'region',
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    /**
     * @param array<int, UsernameSourceCheckResultDTO> $items
     * @return array<int, array<string, mixed>>
     */
    private function serializeItems(array $items): array
    {
        return array_map(
            static fn (UsernameSourceCheckResultDTO $item): array => $item->toArray(),
            $items
        );
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, UsernameSourceCheckResultDTO>
     */
    private function hydrateItems(array $items): array
    {
        return array_map(
            static fn (array $item): UsernameSourceCheckResultDTO => UsernameSourceCheckResultDTO::fromArray($item),
            $items
        );
    }
}
