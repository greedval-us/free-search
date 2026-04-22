<?php

namespace App\Modules\Fio\Domain\DTO;

final class FioSummaryDTO
{
    public function __construct(
        public readonly int $matches,
        public readonly int $domains,
        public readonly string $topRegion,
        public readonly string $topAgeBucket,
        public readonly ?int $medianAge,
        public readonly float $averageConfidence,
        public readonly int $qualifierMatches,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'matches' => $this->matches,
            'domains' => $this->domains,
            'topRegion' => $this->topRegion,
            'topAgeBucket' => $this->topAgeBucket,
            'medianAge' => $this->medianAge,
            'averageConfidence' => $this->averageConfidence,
            'qualifierMatches' => $this->qualifierMatches,
        ];
    }
}
