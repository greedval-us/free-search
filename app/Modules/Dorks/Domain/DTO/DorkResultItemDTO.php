<?php

namespace App\Modules\Dorks\Domain\DTO;

final class DorkResultItemDTO
{
    public function __construct(
        public readonly string $source,
        public readonly string $goal,
        public readonly string $dork,
        public readonly string $title,
        public readonly string $snippet,
        public readonly string $url,
        public readonly ?string $domain,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'goal' => $this->goal,
            'dork' => $this->dork,
            'title' => $this->title,
            'snippet' => $this->snippet,
            'url' => $this->url,
            'domain' => $this->domain,
        ];
    }
}

