<?php

namespace App\Modules\YouTube\DTO\Parser;

use App\Support\Contracts\ArrayPayloadable;

final readonly class YouTubeParserSnapshotDTO implements ArrayPayloadable
{
    /**
     * @param array<int, array<string, mixed>> $commentsIndex
     * @param array<int, array<string, mixed>> $repliesIndex
     */
    public function __construct(
        private string $videoId,
        private array $commentsIndex,
        private array $repliesIndex,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'videoId' => $this->videoId,
            'commentsCount' => count($this->commentsIndex),
            'repliesCount' => count($this->repliesIndex),
            'commentsIndex' => $this->commentsIndex,
            'repliesIndex' => $this->repliesIndex,
        ];
    }
}
