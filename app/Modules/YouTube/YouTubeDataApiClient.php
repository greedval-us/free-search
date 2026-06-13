<?php

namespace App\Modules\YouTube;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Exceptions\Public\IntegrationMisconfiguredException;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Support\YouTubeApiConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class YouTubeDataApiClient implements YouTubeGatewayInterface
{
    public function __construct(
        private readonly YouTubeApiConfig $config,
    ) {
    }

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

    public function comments(array $params): array
    {
        return $this->get('comments', [
            ...$params,
            'part' => $params['part'] ?? 'snippet',
            'textFormat' => $params['textFormat'] ?? 'plainText',
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query): array
    {
        $key = $this->config->apiKey();

        if ($key === '') {
            throw new IntegrationMisconfiguredException('errors.api.youtube.not_configured', 'youtube_not_configured');
        }

        try {
            $response = $this->http()
                ->get($endpoint, [
                    ...$query,
                    'key' => $key,
                ]);
        } catch (ConnectionException $exception) {
            throw new ExternalServiceUnavailableException(
                'errors.api.youtube.unavailable',
                'youtube_unavailable',
                previous: $exception,
            );
        }

        if ($response->failed()) {
            $status = $response->status();

            throw new ExternalServiceRequestException(
                $status === 429 ? 'errors.api.youtube.rate_limited' : 'errors.api.youtube.request_failed',
                $status,
                $status === 429 ? 'youtube_rate_limited' : 'youtube_request_failed',
            );
        }

        return $response->json() ?? [];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->config->baseUrl())
            ->acceptJson()
            ->timeout($this->config->timeoutSeconds())
            ->retry(
                $this->config->retryAttempts(),
                $this->config->retryDelayMilliseconds(),
                throw: false
            );
    }
}
