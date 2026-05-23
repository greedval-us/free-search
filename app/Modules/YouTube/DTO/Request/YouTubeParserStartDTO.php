<?php

namespace App\Modules\YouTube\DTO\Request;

final readonly class YouTubeParserStartDTO
{
    public function __construct(
        public int $userId,
        public string $videoId,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toContext(): array
    {
        return [
            'videoId' => $this->videoId,
        ];
    }
}
