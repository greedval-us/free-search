<?php

namespace App\Modules\Bluesky\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final class BlueskySearchResultDTO implements ArrayPayloadable
{
    /**
     * @param array<int, array<string, mixed>> $posts
     * @param array<int, array<string, mixed>> $actors
     * @param array{query: string, type: string, limit: int, sort: string} $meta
     * @param array{cursor: string|null, posts: array{nextCursor: string|null, hasMore: bool}, actors: array{nextCursor: string|null, hasMore: bool}} $pagination
     */
    public function __construct(
        public readonly array $posts,
        public readonly array $actors,
        public readonly array $meta,
        public readonly array $pagination,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'posts' => $this->posts,
            'actors' => $this->actors,
            'meta' => $this->meta,
            'pagination' => $this->pagination,
        ];
    }
}
