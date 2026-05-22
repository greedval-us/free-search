<?php

namespace App\Modules\YouTube\Support;

class YouTubeUrlBuilder
{
    public function video(string $videoId): string
    {
        return $videoId !== '' ? "https://www.youtube.com/watch?v={$videoId}" : '';
    }

    public function channel(string $channelId): string
    {
        return $channelId !== '' ? "https://www.youtube.com/channel/{$channelId}" : '';
    }

    public function playlist(string $playlistId): string
    {
        return $playlistId !== '' ? "https://www.youtube.com/playlist?list={$playlistId}" : '';
    }

    public function searchItem(string $id, string $type): string
    {
        return match ($type) {
            'channel' => $this->channel($id),
            'playlist' => $this->playlist($id),
            default => $this->video($id),
        };
    }
}
