<?php

namespace App\Modules\YouTube\DTO\Request;

final readonly class YouTubeCommentsQueryDTO
{
    public function __construct(
        public string $videoId,
        public int $maxResults,
        public string $order,
        public string $pageToken = '',
        public string $searchTerms = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $params = [
            'videoId' => $this->videoId,
            'maxResults' => $this->maxResults,
            'order' => $this->order,
        ];

        if ($this->pageToken !== '') {
            $params['pageToken'] = $this->pageToken;
        }

        if ($this->searchTerms !== '') {
            $params['searchTerms'] = $this->searchTerms;
        }

        return $params;
    }
}
