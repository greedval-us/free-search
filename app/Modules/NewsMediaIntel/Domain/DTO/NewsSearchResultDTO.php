<?php

namespace App\Modules\NewsMediaIntel\Domain\DTO;

final readonly class NewsSearchResultDTO
{
    /** @param list<NewsMentionDTO> $mentions */
    public function __construct(
        public array $mentions,
        public array $coverage,
        public array $suggestions = [],
        public array $corrections = [],
        public array $answers = [],
        public array $infoboxes = [],
    ) {}

    public function toArray(): array
    {
        return ['mentions' => array_map(static fn (NewsMentionDTO $mention): array => $mention->toArray(), $this->mentions),
            'coverage' => $this->coverage, 'suggestions' => $this->suggestions, 'corrections' => $this->corrections,
            'answers' => $this->answers, 'infoboxes' => $this->infoboxes];
    }
}
