<?php

namespace App\Modules\YouTube\Presenters;

use App\Modules\YouTube\Support\YouTubeUrlBuilder;
use Illuminate\Support\Arr;

class YouTubeSearchItemPresenter
{
    public function __construct(private readonly YouTubeUrlBuilder $urlBuilder) {}

    /**
     * @param  array<string, array<string, mixed>>  $videoDetailsById
     * @return array<string, mixed>
     */
    public function present(array $item, array $videoDetailsById, string $type): array
    {
        $id = $this->id($item, $type);
        $detail = $type === 'video' ? ($videoDetailsById[$id] ?? []) : [];

        return [
            ...$detail,
            'id' => $id,
            'type' => $type,
            'title' => (string) Arr::get($item, 'snippet.title', Arr::get($detail, 'title', '')),
            'description' => (string) Arr::get($item, 'snippet.description', Arr::get($detail, 'description', '')),
            'channelId' => (string) Arr::get($item, 'snippet.channelId', Arr::get($detail, 'channelId', '')),
            'channelTitle' => (string) Arr::get($item, 'snippet.channelTitle', Arr::get($detail, 'channelTitle', '')),
            'publishedAt' => (string) Arr::get($item, 'snippet.publishedAt', Arr::get($detail, 'publishedAt', '')),
            'thumbnail' => (string) Arr::get($item, 'snippet.thumbnails.medium.url', Arr::get($detail, 'thumbnail', '')),
            'duration' => (string) Arr::get($detail, 'duration', ''),
            'durationSeconds' => (int) Arr::get($detail, 'durationSeconds', 0),
            'durationLabel' => (string) Arr::get($detail, 'durationLabel', ''),
            'categoryId' => (string) Arr::get($detail, 'categoryId', ''),
            'tags' => (array) Arr::get($detail, 'tags', []),
            'defaultLanguage' => (string) Arr::get($detail, 'defaultLanguage', ''),
            'defaultAudioLanguage' => (string) Arr::get($detail, 'defaultAudioLanguage', ''),
            'definition' => (string) Arr::get($detail, 'definition', ''),
            'caption' => (string) Arr::get($detail, 'caption', ''),
            'dimension' => (string) Arr::get($detail, 'dimension', ''),
            'projection' => (string) Arr::get($detail, 'projection', ''),
            'licensedContent' => (bool) Arr::get($detail, 'licensedContent', false),
            'privacyStatus' => (string) Arr::get($detail, 'privacyStatus', ''),
            'embeddable' => (bool) Arr::get($detail, 'embeddable', false),
            'license' => (string) Arr::get($detail, 'license', ''),
            'madeForKids' => (bool) Arr::get($detail, 'madeForKids', false),
            'views' => (int) Arr::get($detail, 'views', 0),
            'likes' => (int) Arr::get($detail, 'likes', 0),
            'comments' => (int) Arr::get($detail, 'comments', 0),
            'favorites' => (int) Arr::get($detail, 'favorites', 0),
            'engagementRate' => (float) Arr::get($detail, 'engagementRate', 0.0),
            'url' => $this->urlBuilder->searchItem($id, $type),
        ];
    }

    private function id(array $item, string $type): string
    {
        return (string) match ($type) {
            'channel' => Arr::get($item, 'id.channelId', ''),
            'playlist' => Arr::get($item, 'id.playlistId', ''),
            default => Arr::get($item, 'id.videoId', ''),
        };
    }
}
