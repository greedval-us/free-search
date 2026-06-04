<?php

namespace App\Modules\Bluesky\DTO\Request;

final readonly class BlueskyParserStartDTO
{
    public function __construct(
        public int $userId,
        public string $actor,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toContext(): array
    {
        return [
            'actor' => $this->actor,
        ];
    }
}
