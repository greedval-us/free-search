<?php

namespace App\Modules\YouTube;

use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YouTubeDataApiClient implements YouTubeGatewayInterface
{
    public function search(array $params): array
    {
        return $this->get('search', [
            ...$params,
            'part' => $params['part'] ?? 'snippet',
        ]);
    }

    public function videos(array $params): array
    {
        return $this->get('videos', [
            ...$params,
            'part' => $params['part'] ?? 'snippet,statistics,contentDetails,status',
        ]);
    }

    public function channels(array $params): array
    {
        return $this->get('channels', [
            ...$params,
            'part' => $params['part'] ?? 'snippet,statistics,contentDetails,topicDetails,status,brandingSettings',
        ]);
    }

    public function commentThreads(array $params): array
    {
        return $this->get('commentThreads', [
            ...$params,
            'part' => $params['part'] ?? 'snippet,replies',
            'textFormat' => $params['textFormat'] ?? 'plainText',
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query): array
    {
        $key = trim((string) config('services.youtube.key', ''));

        if ($key === '') {
            throw new RuntimeException('YOUTUBE_DATA_API_KEY is not configured.');
        }

        try {
            $response = $this->http()
                ->get($endpoint, [
                    ...$query,
                    'key' => $key,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to YouTube API. Check internet connection and API key restrictions.', 503, previous: $exception);
        }

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
            ->retry(2, 250, throw: false);
    }
}
