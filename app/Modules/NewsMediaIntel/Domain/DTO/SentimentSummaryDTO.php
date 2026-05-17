<?php

namespace App\Modules\NewsMediaIntel\Domain\DTO;

final class SentimentSummaryDTO
{
    public function __construct(
        public readonly int $positive,
        public readonly int $neutral,
        public readonly int $negative,
    ) {
    }

    /**
     * @return array{positive: int, neutral: int, negative: int}
     */
    public function toArray(): array
    {
        return [
            'positive' => $this->positive,
            'neutral' => $this->neutral,
            'negative' => $this->negative,
        ];
    }
}

