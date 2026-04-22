<?php

namespace App\Modules\Fio\Domain\Services;

final class FioSourceReliabilityResolver
{
    /**
     * @var array<string, float>
     */
    private array $weights = [
        'duckduckgo' => 0.95,
        'bing' => 0.9,
    ];

    public function weight(string $source): float
    {
        $key = mb_strtolower(trim($source));

        return $this->weights[$key] ?? 0.8;
    }
}
