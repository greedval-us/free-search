<?php

namespace App\Modules\YouTube\DTO\Result;

class YouTubeParserRunStatusDTO
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

