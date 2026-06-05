<?php

namespace App\Modules\Bluesky\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final class BlueskyParserRunStatusDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly array $payload,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
