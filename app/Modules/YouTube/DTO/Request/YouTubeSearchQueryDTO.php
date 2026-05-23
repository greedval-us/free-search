<?php

namespace App\Modules\YouTube\DTO\Request;

final readonly class YouTubeSearchQueryDTO
{
    public function __construct(
        public string $query,
        public string $type,
        public int $maxResults,
        public string $order,
        public string $safeSearch,
        public string $channelId = '',
        public string $regionCode = '',
        public string $relevanceLanguage = '',
        public string $pageToken = '',
        public string $videoDuration = 'any',
        public string $videoDefinition = 'any',
        public string $videoCaption = 'any',
        public ?string $publishedAfter = null,
        public ?string $publishedBefore = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $params = [
            'q' => $this->query,
            'type' => $this->type,
            'maxResults' => $this->maxResults,
            'order' => $this->order,
            'safeSearch' => $this->safeSearch,
        ];

        foreach (['channelId', 'regionCode', 'relevanceLanguage', 'pageToken'] as $field) {
            $value = $this->{$field};

            if ($value !== '') {
                $params[$field] = $value;
            }
        }

        if ($this->publishedAfter !== null) {
            $params['publishedAfter'] = $this->publishedAfter;
        }

        if ($this->publishedBefore !== null) {
            $params['publishedBefore'] = $this->publishedBefore;
        }

        if ($this->type === 'video') {
            foreach (['videoDuration', 'videoDefinition', 'videoCaption'] as $field) {
                $value = $this->{$field};

                if ($value !== 'any') {
                    $params[$field] = $value;
                }
            }
        }

        return $params;
    }
}
