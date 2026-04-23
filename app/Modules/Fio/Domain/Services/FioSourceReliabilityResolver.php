<?php

namespace App\Modules\Fio\Domain\Services;

final class FioSourceReliabilityResolver
{
    /**
     * @var array<string, float>
     */
    private array $weights;

    public function __construct()
    {
        $configWeights = config('fio.source_reliability', []);
        $this->weights = $this->normalizeWeights($configWeights);
    }

    public function weight(string $source): float
    {
        $key = mb_strtolower(trim($source));

        return $this->weights[$key] ?? 0.8;
    }

    /**
     * @param mixed $weights
     * @return array<string, float>
     */
    private function normalizeWeights(mixed $weights): array
    {
        if (!is_array($weights)) {
            return [];
        }

        $normalized = [];

        foreach ($weights as $source => $weight) {
            if (!is_string($source) || !is_numeric($weight)) {
                continue;
            }

            $numericWeight = (float) $weight;
            $normalized[mb_strtolower(trim($source))] = max(0.0, min(1.0, $numericWeight));
        }

        return $normalized;
    }
}
