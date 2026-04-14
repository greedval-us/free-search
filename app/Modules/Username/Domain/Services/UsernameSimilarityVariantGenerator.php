<?php

namespace App\Modules\Username\Domain\Services;

final class UsernameSimilarityVariantGenerator
{
    /**
     * @return array<int, array{username: string, reason: string}>
     */
    public function generate(string $username): array
    {
        $base = mb_strtolower(trim($username));

        if ($base === '') {
            return [];
        }

        $normalized = preg_replace('/[._-]+/u', '', $base) ?? $base;
        $segments = array_values(array_filter(preg_split('/[._-]+/u', $base) ?: []));
        $max = (int) config('username.analytics.similarity.max_variants', 8);
        $rules = $this->rules();
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

        return array_slice(array_values($variants), 0, max(1, $max));
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
    private function rules(): array
    {
        $rules = (array) config('username.analytics.similarity.rules', []);

        $separators = $this->normalizeStringList($rules['separators'] ?? [], []);
        $prefixes = $this->normalizeStringList($rules['prefixes'] ?? [], []);
        $suffixes = $this->normalizeStringList($rules['suffixes'] ?? [], []);
        $numericSuffixes = $this->normalizeStringList($rules['numeric_suffixes'] ?? [], []);

        $leetSubstitutions = [];
        $rawLeet = $rules['leet_substitutions'] ?? [];

        if (is_array($rawLeet)) {
            foreach ($rawLeet as $source => $target) {
                $sourceKey = mb_strtolower(trim((string) $source));
                $targetValue = trim((string) $target);

                if ($sourceKey !== '' && $targetValue !== '') {
                    $leetSubstitutions[$sourceKey] = $targetValue;
                }
            }
        }

        return [
            'separators' => $separators,
            'prefixes' => $prefixes,
            'suffixes' => $suffixes,
            'numeric_suffixes' => $numericSuffixes,
            'leet_substitutions' => $leetSubstitutions,
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
     * @param array<int, mixed> $value
     * @param array<int, string> $fallback
     * @return array<int, string>
     */
    private function normalizeStringList(array $value, array $fallback): array
    {
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
}
