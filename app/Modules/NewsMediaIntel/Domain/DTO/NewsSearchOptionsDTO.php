<?php

namespace App\Modules\NewsMediaIntel\Domain\DTO;

final readonly class NewsSearchOptionsDTO
{
    /** @param list<string> $categories @param list<string> $engines */
    public function __construct(
        public array $categories = ['news'],
        public string $language = '',
        public ?string $timeRange = null,
        public int $safeSearch = -1,
        public array $engines = [],
        public int $maxPages = 0,
    ) {}

    public function toArray(): array
    {
        return ['categories' => $this->categories, 'language' => $this->language, 'timeRange' => $this->timeRange,
            'safeSearch' => $this->safeSearch, 'engines' => $this->engines, 'maxPages' => $this->maxPages];
    }
}
