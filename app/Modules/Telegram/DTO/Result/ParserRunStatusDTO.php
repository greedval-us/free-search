<?php

namespace App\Modules\Telegram\DTO\Result;

class ParserRunStatusDTO
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
