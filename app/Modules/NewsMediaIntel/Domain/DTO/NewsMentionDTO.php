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
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'title' => $this->title,
            'snippet' => $this->snippet,
            'link' => $this->link,
            'publishedAt' => $this->publishedAt,
        ];
    }
}

