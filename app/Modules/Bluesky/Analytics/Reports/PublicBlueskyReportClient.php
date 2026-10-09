<?php

namespace App\Modules\Bluesky\Analytics\Reports;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\Bluesky\Analytics\Reports\Contracts\ScheduledBlueskyGatewayInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class PublicBlueskyReportClient implements ScheduledBlueskyGatewayInterface
{
    public function getProfiles(array $actors): array
    {
        return $this->get('/xrpc/app.bsky.actor.getProfiles', ['actors' => $actors]);
    }

    public function getAuthorFeed(string $actor, int $limit, ?string $cursor = null): array
    {
        return $this->get('/xrpc/app.bsky.feed.getAuthorFeed', array_filter([
            'actor' => $actor, 'limit' => $limit, 'cursor' => $cursor, 'filter' => 'posts_with_replies',
        ], static fn (mixed $value): bool => $value !== null));
    }

    private function get(string $endpoint, array $query): array
    {
        try {
            // Public analytics never follows a submitted account hostname or logs in as a viewer.
            $response = Http::baseUrl('https://public.api.bsky.app')->acceptJson()->withoutRedirecting()
                ->timeout(20)->retry(2, 250, throw: false)->get($endpoint, $query);
        } catch (ConnectionException $exception) {
            throw new ExternalServiceUnavailableException('errors.api.bluesky.unavailable', 'bluesky_unavailable', previous: $exception);
        }
        if (! $response->successful()) {
            throw new ExternalServiceRequestException('errors.api.bluesky.request_failed', $response->status(), 'bluesky_request_failed');
        }
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new ExternalServiceRequestException('errors.api.bluesky.request_failed', 502, 'bluesky_invalid_response');
        }

        return $payload;
    }
}
