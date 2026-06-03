<?php

namespace App\Modules\Bluesky\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final class BlueskyThreadResultDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed>|null $root
     * @param array<int, array<string, mixed>> $ancestors
     * @param array<int, array<string, mixed>> $replies
     */
    public function __construct(
        public readonly ?array $root,
        public readonly array $ancestors,
        public readonly array $replies,
        public readonly array $meta,
    ) {
    }

    public function toArray(): array
    {
        return [
            'root' => $this->root,
            'ancestors' => $this->ancestors,
            'replies' => $this->replies,
            'meta' => $this->meta,
        ];
    }
}
