<?php

namespace App\Modules\YouTube\Presenters;

use App\Modules\YouTube\Support\YouTubeUrlBuilder;
use Illuminate\Support\Arr;

class YouTubeChannelPresenter
{
    public function __construct(private readonly YouTubeUrlBuilder $urlBuilder) {}

    /**
     * @return array<string, mixed>
     */
    public function present(array $item): array
    {
        $statistics = $item['statistics'] ?? [];
        $channelId = (string) ($item['id'] ?? '');

        return [
            'id' => $channelId,
            'title' => (string) Arr::get($item, 'snippet.title', ''),
            'description' => (string) Arr::get($item, 'snippet.description', ''),
            'customUrl' => (string) Arr::get($item, 'snippet.customUrl', ''),
            'publishedAt' => (string) Arr::get($item, 'snippet.publishedAt', ''),
            'country' => (string) Arr::get($item, 'snippet.country', ''),
            'thumbnail' => (string) Arr::get($item, 'snippet.thumbnails.medium.url', Arr::get($item, 'snippet.thumbnails.default.url', '')),
            'uploadsPlaylistId' => (string) Arr::get($item, 'contentDetails.relatedPlaylists.uploads', ''),
            'viewCount' => (int) ($statistics['viewCount'] ?? 0),
            'subscriberCount' => (int) ($statistics['subscriberCount'] ?? 0),
            'hiddenSubscriberCount' => (bool) ($statistics['hiddenSubscriberCount'] ?? false),
            'videoCount' => (int) ($statistics['videoCount'] ?? 0),
            'privacyStatus' => (string) Arr::get($item, 'status.privacyStatus', ''),
            'madeForKids' => (bool) Arr::get($item, 'status.madeForKids', false),
            'keywords' => (string) Arr::get($item, 'brandingSettings.channel.keywords', ''),
            'topicCategories' => array_values((array) Arr::get($item, 'topicDetails.topicCategories', [])),
            'url' => $this->urlBuilder->channel($channelId),
        ];
    }
}
