<?php

namespace App\Modules\YouTube\Presenters;

use App\Modules\YouTube\Support\YouTubeDurationFormatter;
use App\Modules\YouTube\Support\YouTubeUrlBuilder;
use Illuminate\Support\Arr;

class YouTubeVideoPresenter
{
    public function __construct(
        private readonly YouTubeDurationFormatter $durationFormatter,
        private readonly YouTubeUrlBuilder $urlBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(array $item): array
    {
        $statistics = $item['statistics'] ?? [];
        $contentDetails = $item['contentDetails'] ?? [];
        $status = $item['status'] ?? [];
        $videoId = (string) ($item['id'] ?? '');
        $duration = (string) Arr::get($contentDetails, 'duration', '');
        $durationSeconds = $this->durationFormatter->seconds($duration);

        return [
            'id' => $videoId,
            'type' => 'video',
            'title' => (string) Arr::get($item, 'snippet.title', ''),
            'description' => (string) Arr::get($item, 'snippet.description', ''),
            'channelId' => (string) Arr::get($item, 'snippet.channelId', ''),
            'channelTitle' => (string) Arr::get($item, 'snippet.channelTitle', ''),
            'publishedAt' => (string) Arr::get($item, 'snippet.publishedAt', ''),
            'thumbnail' => (string) Arr::get($item, 'snippet.thumbnails.medium.url', Arr::get($item, 'snippet.thumbnails.default.url', '')),
            'duration' => $duration,
            'durationSeconds' => $durationSeconds,
            'durationLabel' => $this->durationFormatter->label($durationSeconds),
            'categoryId' => (string) Arr::get($item, 'snippet.categoryId', ''),
            'tags' => array_values((array) Arr::get($item, 'snippet.tags', [])),
            'defaultLanguage' => (string) Arr::get($item, 'snippet.defaultLanguage', ''),
            'defaultAudioLanguage' => (string) Arr::get($item, 'snippet.defaultAudioLanguage', ''),
            'definition' => (string) Arr::get($contentDetails, 'definition', ''),
            'caption' => (string) Arr::get($contentDetails, 'caption', ''),
            'dimension' => (string) Arr::get($contentDetails, 'dimension', ''),
            'projection' => (string) Arr::get($contentDetails, 'projection', ''),
            'licensedContent' => (bool) Arr::get($contentDetails, 'licensedContent', false),
            'privacyStatus' => (string) Arr::get($status, 'privacyStatus', ''),
            'embeddable' => (bool) Arr::get($status, 'embeddable', false),
            'license' => (string) Arr::get($status, 'license', ''),
            'madeForKids' => (bool) Arr::get($status, 'madeForKids', false),
            'views' => (int) ($statistics['viewCount'] ?? 0),
            'likes' => (int) ($statistics['likeCount'] ?? 0),
            'comments' => (int) ($statistics['commentCount'] ?? 0),
            'favorites' => (int) ($statistics['favoriteCount'] ?? 0),
            'engagementRate' => $this->engagementRate($statistics),
            'url' => $this->urlBuilder->video($videoId),
        ];
    }

    private function engagementRate(array $statistics): float
    {
        $views = (int) ($statistics['viewCount'] ?? 0);

        if ($views <= 0) {
            return 0.0;
        }

        return round((((int) ($statistics['likeCount'] ?? 0) + (int) ($statistics['commentCount'] ?? 0)) / $views) * 100, 2);
    }
}
