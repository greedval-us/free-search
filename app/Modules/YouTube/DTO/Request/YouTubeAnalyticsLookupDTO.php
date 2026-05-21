<?php

namespace App\Modules\YouTube\DTO\Request;

final readonly class YouTubeAnalyticsLookupDTO
{
    public function __construct(
        public string $mode,
        public string $videoId,
        public string $channelId,
        public int $limit,
    ) {}

    /**
     * @return array{mode: string, videoId: string, channelId: string, limit: int}
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'videoId' => $this->videoId,
            'channelId' => $this->channelId,
            'limit' => $this->limit,
        ];
    }
}
