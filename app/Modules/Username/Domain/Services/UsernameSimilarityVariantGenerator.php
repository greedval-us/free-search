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

        $this->addTypoVariants($variants, $normalized, $base, $rules);
        $this->addTransliterationVariants($variants, $base, $normalized, $rules);

        return array_slice(array_values($variants), 0, max(1, $max));
    }

    /**
     * @return array{
     *   separators: array<int, string>,
     *   prefixes: array<int, string>,
     *   suffixes: array<int, string>,
     *   numeric_suffixes: array<int, string>,
     *   leet_substitutions: array<string, string>,
     *   typo_substitutions: array<string, array<int, string>>,
     *   transliteration_map: array<string, string>
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

        $typoSubstitutions = [];
        $rawTypos = $rules['typo_substitutions'] ?? [];

        if (is_array($rawTypos)) {
            foreach ($rawTypos as $source => $targets) {
                $sourceKey = mb_strtolower(trim((string) $source));

                if ($sourceKey === '') {
                    continue;
                }

                if (!is_array($targets)) {
                    continue;
                }

                $typoTargets = [];

                foreach ($targets as $target) {
                    $targetValue = mb_strtolower(trim((string) $target));

                    if ($targetValue !== '') {
                        $typoTargets[] = $targetValue;
                    }
                }

                if ($typoTargets !== []) {
                    $typoSubstitutions[$sourceKey] = array_values(array_unique($typoTargets));
                }
            }
        }

        $transliterationMap = [];
        $rawTransliteration = $rules['transliteration_map'] ?? [];

        if (is_array($rawTransliteration)) {
            foreach ($rawTransliteration as $source => $target) {
                $sourceKey = mb_strtolower(trim((string) $source));
                $targetValue = mb_strtolower(trim((string) $target));

                if ($sourceKey !== '' && $targetValue !== '') {
                    $transliterationMap[$sourceKey] = $targetValue;
                }
            }
        }

        return [
            'separators' => $separators,
            'prefixes' => $prefixes,
            'suffixes' => $suffixes,
            'numeric_suffixes' => $numericSuffixes,
            'leet_substitutions' => $leetSubstitutions,
            'typo_substitutions' => $typoSubstitutions,
            'transliteration_map' => $transliterationMap,
        ];
    }

    /**
     * @param array<string, array{username: string, reason: string}> $variants
     * @param array{
     *   typo_substitutions: array<string, array<int, string>>
     * } $rules
     */
    private function addTypoVariants(array &$variants, string $normalized, string $base, array $rules): void
    {
        $length = mb_strlen($normalized);

        if ($length < 3) {
            return;
        }

        // Adjacent transposition: "greed" -> "gered"
        for ($index = 0; $index < $length - 1; $index++) {
            $left = mb_substr($normalized, $index, 1);
            $right = mb_substr($normalized, $index + 1, 1);

            $candidate = mb_substr($normalized, 0, $index)
                .$right
                .$left
                .mb_substr($normalized, $index + 2);

            $this->addVariant($variants, $candidate, 'typo_transpose', $base);
        }

        // Omission: "greed" -> "gred"
        for ($index = 0; $index < $length; $index++) {
            $candidate = mb_substr($normalized, 0, $index).mb_substr($normalized, $index + 1);
            $this->addVariant($variants, $candidate, 'typo_omit', $base);
        }

        // Double character: "greed" -> "greeed"
        for ($index = 0; $index < $length; $index++) {
            $char = mb_substr($normalized, $index, 1);
            $candidate = mb_substr($normalized, 0, $index + 1).$char.mb_substr($normalized, $index + 1);
            $this->addVariant($variants, $candidate, 'typo_double', $base);
        }

        // Keyboard-near substitutions from config.
        foreach ($rules['typo_substitutions'] as $sourceChar => $targets) {
            if (!str_contains($normalized, $sourceChar)) {
                continue;
            }

            foreach ($targets as $targetChar) {
                $candidate = str_replace($sourceChar, $targetChar, $normalized);
                $this->addVariant($variants, $candidate, 'typo_keyboard', $base);
            }
        }
    }

    /**
     * @param array<string, array{username: string, reason: string}> $variants
     * @param array{
     *   transliteration_map: array<string, string>
     * } $rules
     */
    private function addTransliterationVariants(array &$variants, string $base, string $normalized, array $rules): void
    {
        $map = $rules['transliteration_map'];

        if ($map === []) {
            return;
        }

        if (preg_match('/[\x{0401}\x{0410}-\x{044F}]/u', $base) === 1) {
            $transliterated = strtr($base, $map);
            $transliterated = preg_replace('/[^a-z0-9._-]/u', '', $transliterated) ?? '';
            $this->addVariant($variants, $transliterated, 'transliteration', $base);
        }

        if (preg_match('/^(sh|zh|kh|ch|yu|ya|yo|ts)/', $normalized) === 1) {
            $this->addVariant($variants, preg_replace('/^ya/', 'ia', $normalized) ?? $normalized, 'romanization', $base);
            $this->addVariant($variants, preg_replace('/^yu/', 'iu', $normalized) ?? $normalized, 'romanization', $base);
            $this->addVariant($variants, preg_replace('/^yo/', 'io', $normalized) ?? $normalized, 'romanization', $base);
        }
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
