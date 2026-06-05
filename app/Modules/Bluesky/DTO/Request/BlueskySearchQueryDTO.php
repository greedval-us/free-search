<?php

namespace App\Modules\Bluesky\DTO\Request;

final readonly class BlueskySearchQueryDTO
{
    public function __construct(
        public string $query,
        public string $type,
        public int $limit,
        public string $cursor = '',
        public string $sort = 'latest',
        public string $language = '',
        public string $author = '',
        public string $mentions = '',
        public string $domain = '',
        public string $url = '',
        public string $tag = '',
        public string $since = '',
        public string $until = '',
    ) {
    }

    public function includesPosts(): bool
    {
        return in_array($this->type, ['all', 'posts'], true);
    }

    public function includesActors(): bool
    {
        return in_array($this->type, ['all', 'actors'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPostParams(): array
    {
        return array_filter([
            'q' => $this->query,
            'limit' => $this->limit,
            'cursor' => $this->cursor !== '' ? $this->cursor : null,
            'sort' => $this->sort,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toActorParams(): array
    {
        return array_filter([
            'q' => $this->query,
            'limit' => $this->limit,
            'cursor' => $this->cursor !== '' ? $this->cursor : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
