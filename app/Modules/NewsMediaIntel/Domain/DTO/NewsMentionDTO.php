<?php

namespace App\Modules\NewsMediaIntel\Domain\DTO;

final class NewsMentionDTO
{
    public function __construct(
        public readonly string $source,
        public readonly string $title,
        public readonly string $snippet,
        public readonly string $link,
        public readonly string $publishedAt,
        /** @var list<string> */
        public readonly array $engines = [],
        public readonly string $category = 'news',
        public readonly ?int $position = null,
        public readonly string $publisher = '',
        /** @var list<string> */
        public readonly array $categories = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'title' => $this->title,
            'snippet' => $this->snippet,
            'link' => $this->link,
            'publishedAt' => $this->publishedAt,
            'engines' => $this->engines,
            'category' => $this->category,
            'position' => $this->position,
            'publisher' => $this->publisher,
            'categories' => $this->categories !== [] ? $this->categories : [$this->category],
        ];
    }
}
