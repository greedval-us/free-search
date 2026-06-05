<?php

namespace App\Modules\Bluesky\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final class BlueskyPostListResultDTO implements ArrayPayloadable
{
    /**
     * @param array<int, array<string, mixed>> $items
     * @param array{limit: int, cursor: string|null, nextCursor: string|null, hasMore: bool} $pagination
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly array $items,
        public readonly array $pagination,
        public readonly array $meta,
    ) {
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'pagination' => $this->pagination,
            'meta' => $this->meta,
        ];
    }
}
