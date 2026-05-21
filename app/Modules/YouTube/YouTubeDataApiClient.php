<?php

namespace App\Modules\YouTube;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YouTubeDataApiClient
{
    public function searchVideos(array $params): array
    {
        $payload = $this->get('search', [
            ...$params,
            'part' => 'snippet',
            'type' => 'video',
        ]);

        $videoIds = collect($payload['items'] ?? [])
            ->map(fn (array $item): ?string => Arr::get($item, 'id.videoId'))
            ->filter()
            ->values()
            ->all();

        $detailsById = $this->videosById($videoIds);

        return [
            'items' => collect($payload['items'] ?? [])
                ->map(fn (array $item): array => $this->presentSearchItem($item, $detailsById))
                ->values()
                ->all(),
            'pagination' => [
                'nextPageToken' => $payload['nextPageToken'] ?? null,
                'prevPageToken' => $payload['prevPageToken'] ?? null,
                'total' => (int) Arr::get($payload, 'pageInfo.totalResults', 0),
                'perPage' => (int) Arr::get($payload, 'pageInfo.resultsPerPage', 0),
            ],
        ];
    }

    public function analyticsSummary(array $params): array
    {
        if ($params['mode'] === 'channel' || $params['channelId'] !== '') {
            return $this->channelSummary($params['channelId'], $params['limit']);
        }

        return $this->videoSummary($params['videoId']);
    }

    public function videoComments(array $params): array
    {
        $payload = $this->get('commentThreads', [
            ...$params,
            'part' => 'snippet,replies',
            'textFormat' => 'plainText',
        ]);

        return [
            'items' => collect($payload['items'] ?? [])
                ->map(fn (array $item): array => $this->presentCommentThread($item))
                ->values()
                ->all(),
            'pagination' => [
                'nextPageToken' => $payload['nextPageToken'] ?? null,
                'total' => (int) Arr::get($payload, 'pageInfo.totalResults', 0),
                'perPage' => (int) Arr::get($payload, 'pageInfo.resultsPerPage', 0),
            ],
        ];
    }

    private function videoSummary(string $videoId): array
    {
        $video = $this->videosById([$videoId])[$videoId] ?? null;

        if ($video === null) {
            return [
                'mode' => 'video',
                'video' => null,
                'totals' => $this->emptyTotals(),
                'topVideos' => [],
            ];
        }

        return [
            'mode' => 'video',
            'video' => $video,
            'totals' => $this->totals([$video]),
            'topVideos' => [$video],
        ];
    }

    private function channelSummary(string $channelId, int $limit): array
    {
        $search = $this->get('search', [
            'part' => 'snippet',
            'channelId' => $channelId,
            'type' => 'video',
            'order' => 'date',
            'maxResults' => $limit,
        ]);

        $videoIds = collect($search['items'] ?? [])
            ->map(fn (array $item): ?string => Arr::get($item, 'id.videoId'))
            ->filter()
            ->values()
            ->all();

        $videos = array_values($this->videosById($videoIds));

        usort($videos, fn (array $a, array $b): int => ($b['views'] <=> $a['views']));

        return [
            'mode' => 'channel',
            'channelId' => $channelId,
            'video' => null,
            'totals' => $this->totals($videos),
            'topVideos' => $videos,
        ];
    }

    /**
     * @param  array<int, string>  $videoIds
     * @return array<string, array<string, mixed>>
     */
    private function videosById(array $videoIds): array
    {
        if ($videoIds === []) {
            return [];
        }

        $payload = $this->get('videos', [
            'part' => 'snippet,statistics,contentDetails',
            'id' => implode(',', array_unique($videoIds)),
            'maxResults' => 50,
        ]);

        return collect($payload['items'] ?? [])
            ->mapWithKeys(fn (array $item): array => [$item['id'] => $this->presentVideo($item)])
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $detailsById
     */
    private function presentSearchItem(array $item, array $detailsById): array
    {
        $videoId = (string) Arr::get($item, 'id.videoId', '');
        $detail = $detailsById[$videoId] ?? [];

        return [
            ...$detail,
            'id' => $videoId,
            'title' => (string) Arr::get($item, 'snippet.title', Arr::get($detail, 'title', '')),
            'description' => (string) Arr::get($item, 'snippet.description', Arr::get($detail, 'description', '')),
            'channelId' => (string) Arr::get($item, 'snippet.channelId', Arr::get($detail, 'channelId', '')),
            'channelTitle' => (string) Arr::get($item, 'snippet.channelTitle', Arr::get($detail, 'channelTitle', '')),
            'publishedAt' => (string) Arr::get($item, 'snippet.publishedAt', Arr::get($detail, 'publishedAt', '')),
            'thumbnail' => (string) Arr::get($item, 'snippet.thumbnails.medium.url', Arr::get($detail, 'thumbnail', '')),
            'url' => $this->videoUrl($videoId),
        ];
    }

    private function presentVideo(array $item): array
    {
        $statistics = $item['statistics'] ?? [];
        $videoId = (string) $item['id'];

        return [
            'id' => $videoId,
            'title' => (string) Arr::get($item, 'snippet.title', ''),
            'description' => (string) Arr::get($item, 'snippet.description', ''),
            'channelId' => (string) Arr::get($item, 'snippet.channelId', ''),
            'channelTitle' => (string) Arr::get($item, 'snippet.channelTitle', ''),
            'publishedAt' => (string) Arr::get($item, 'snippet.publishedAt', ''),
            'thumbnail' => (string) Arr::get($item, 'snippet.thumbnails.medium.url', Arr::get($item, 'snippet.thumbnails.default.url', '')),
            'duration' => (string) Arr::get($item, 'contentDetails.duration', ''),
            'views' => (int) ($statistics['viewCount'] ?? 0),
            'likes' => (int) ($statistics['likeCount'] ?? 0),
            'comments' => (int) ($statistics['commentCount'] ?? 0),
            'favorites' => (int) ($statistics['favoriteCount'] ?? 0),
            'url' => $this->videoUrl($videoId),
        ];
    }

    private function presentCommentThread(array $item): array
    {
        $snippet = Arr::get($item, 'snippet.topLevelComment.snippet', []);

        return [
            'id' => (string) Arr::get($item, 'snippet.topLevelComment.id', $item['id'] ?? ''),
            'threadId' => (string) ($item['id'] ?? ''),
            'videoId' => (string) Arr::get($item, 'snippet.videoId', ''),
            'author' => (string) Arr::get($snippet, 'authorDisplayName', ''),
            'authorChannelUrl' => (string) Arr::get($snippet, 'authorChannelUrl', ''),
            'text' => (string) Arr::get($snippet, 'textDisplay', ''),
            'likeCount' => (int) Arr::get($snippet, 'likeCount', 0),
            'publishedAt' => (string) Arr::get($snippet, 'publishedAt', ''),
            'updatedAt' => (string) Arr::get($snippet, 'updatedAt', ''),
            'replyCount' => (int) Arr::get($item, 'snippet.totalReplyCount', 0),
            'replies' => collect(Arr::get($item, 'replies.comments', []))
                ->map(fn (array $reply): array => [
                    'id' => (string) ($reply['id'] ?? ''),
                    'author' => (string) Arr::get($reply, 'snippet.authorDisplayName', ''),
                    'text' => (string) Arr::get($reply, 'snippet.textDisplay', ''),
                    'likeCount' => (int) Arr::get($reply, 'snippet.likeCount', 0),
                    'publishedAt' => (string) Arr::get($reply, 'snippet.publishedAt', ''),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     */
    private function totals(array $videos): array
    {
        if ($videos === []) {
            return $this->emptyTotals();
        }

        $views = array_sum(array_column($videos, 'views'));
        $likes = array_sum(array_column($videos, 'likes'));
        $comments = array_sum(array_column($videos, 'comments'));

        return [
            'videos' => count($videos),
            'views' => $views,
            'likes' => $likes,
            'comments' => $comments,
            'avgViews' => (int) round($views / count($videos)),
            'engagementRate' => $views > 0 ? round((($likes + $comments) / $views) * 100, 2) : 0.0,
        ];
    }

    private function emptyTotals(): array
    {
        return [
            'videos' => 0,
            'views' => 0,
            'likes' => 0,
            'comments' => 0,
            'avgViews' => 0,
            'engagementRate' => 0.0,
        ];
    }

    private function get(string $endpoint, array $query): array
    {
        $key = trim((string) config('services.youtube.key', ''));

        if ($key === '') {
            throw new RuntimeException('YOUTUBE_DATA_API_KEY is not configured.');
        }

        $response = $this->http()
            ->get($endpoint, [
                ...$query,
                'key' => $key,
            ]);

        if ($response->failed()) {
            $message = (string) Arr::get($response->json(), 'error.message', 'YouTube API request failed.');

            throw new RuntimeException($message, $response->status());
        }

        return $response->json() ?? [];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.youtube.base_url'), '/'))
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 250);
    }

    private function videoUrl(string $videoId): string
    {
        return $videoId !== '' ? "https://www.youtube.com/watch?v={$videoId}" : '';
    }
}
