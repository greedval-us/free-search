<?php

namespace App\Modules\YouTube\DTO\Request;

final readonly class YouTubeAnalyticsLookupDTO
{
    public function __construct(
        public string $mode,
        public string $videoId,
        public string $channelId,
        public int $periodDays,
        public string $dateFrom,
        public string $dateTo,
    ) {}

    /**
     * @return array{mode: string, videoId: string, channelId: string, periodDays: int, dateFrom: string, dateTo: string}
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'videoId' => $this->videoId,
            'channelId' => $this->channelId,
            'periodDays' => $this->periodDays,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ];
    }
}
