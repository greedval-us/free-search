<?php

namespace App\Modules\Bluesky;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Exceptions\Public\IntegrationMisconfiguredException;
use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use App\Modules\Bluesky\Support\BlueskyApiConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class BlueskyApiClient implements BlueskyGatewayInterface
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $session = null;

    public function __construct(
        private readonly BlueskyApiConfig $config,
    ) {
    }

    public function searchPosts(array $params): array
    {
        return $this->get('/xrpc/app.bsky.feed.searchPosts', $params);
    }

    public function searchActors(array $params): array
    {
        return $this->get('/xrpc/app.bsky.actor.searchActors', $params);
    }

    public function getProfiles(array $actors): array
    {
        $query = [];

        foreach ($actors as $actor) {
            $query['actors'][] = $actor;
        }

        return $this->get('/xrpc/app.bsky.actor.getProfiles', $query);
    }

    public function getLikes(string $uri, ?string $cid, int $limit, ?string $cursor = null): array
    {
        return $this->get('/xrpc/app.bsky.feed.getLikes', array_filter([
            'uri' => $uri,
            'cid' => $cid,
            'limit' => $limit,
            'cursor' => $cursor,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public function getRepostedBy(string $uri, int $limit, ?string $cursor = null): array
    {
        return $this->get('/xrpc/app.bsky.feed.getRepostedBy', array_filter([
            'uri' => $uri,
            'limit' => $limit,
            'cursor' => $cursor,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public function getPostThread(string $uri, int $depth = 6, int $parentHeight = 6): array
    {
        return $this->get('/xrpc/app.bsky.feed.getPostThread', [
            'uri' => $uri,
            'depth' => $depth,
            'parentHeight' => $parentHeight,
        ]);
    }

    public function getAuthorFeed(string $actor, int $limit, ?string $cursor = null, ?string $filter = null): array
    {
        return $this->get('/xrpc/app.bsky.feed.getAuthorFeed', array_filter([
            'actor' => $actor,
            'limit' => $limit,
            'cursor' => $cursor,
            'filter' => $filter,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public function getFollowers(string $actor, int $limit, ?string $cursor = null): array
    {
        return $this->get('/xrpc/app.bsky.graph.getFollowers', array_filter([
            'actor' => $actor,
            'limit' => $limit,
            'cursor' => $cursor,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public function getFollows(string $actor, int $limit, ?string $cursor = null): array
    {
        return $this->get('/xrpc/app.bsky.graph.getFollows', array_filter([
            'actor' => $actor,
            'limit' => $limit,
            'cursor' => $cursor,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query): array
    {
        $response = $this->request($endpoint, $query);

        return $response->json() ?? [];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function request(string $endpoint, array $query): \Illuminate\Http\Client\Response
    {
        $this->guardConfig();

        try {
            $response = $this->http()
                ->withToken($this->accessJwt())
                ->get($endpoint, $query);
        } catch (ConnectionException $exception) {
            throw new ExternalServiceUnavailableException(
                'errors.api.bluesky.unavailable',
                'bluesky_unavailable',
                previous: $exception,
            );
        }

        if ($response->failed()) {
            $status = $response->status();

            throw new ExternalServiceRequestException(
                $status === 429 ? 'errors.api.bluesky.rate_limited' : 'errors.api.bluesky.request_failed',
                $status,
                $status === 429 ? 'bluesky_rate_limited' : 'bluesky_request_failed',
            );
        }

        return $response;
    }

    private function accessJwt(): string
    {
        if ($this->session !== null) {
            return (string) ($this->session['accessJwt'] ?? '');
        }

        try {
            $response = $this->http()->post('/xrpc/com.atproto.server.createSession', [
                'identifier' => $this->config->identifier(),
                'password' => $this->config->appPassword(),
            ]);
        } catch (ConnectionException $exception) {
            throw new ExternalServiceUnavailableException(
                'errors.api.bluesky.unavailable',
                'bluesky_session_unavailable',
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw new ExternalServiceRequestException(
                'errors.api.bluesky.authentication_failed',
                $response->status(),
                'bluesky_authentication_failed',
            );
        }

        $session = $response->json() ?? [];
        $accessJwt = trim((string) ($session['accessJwt'] ?? ''));

        if ($accessJwt === '') {
            throw new ExternalServiceUnavailableException(
                'errors.api.bluesky.authentication_failed',
                'bluesky_missing_access_token',
            );
        }

        $this->session = $session;

        return $accessJwt;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->config->pdsUrl())
            ->acceptJson()
            ->asJson()
            ->timeout($this->config->timeoutSeconds())
            ->retry(
                $this->config->retryAttempts(),
                $this->config->retryDelayMilliseconds(),
                throw: false,
            );
    }

    private function guardConfig(): void
    {
        if ($this->config->identifier() === '') {
            throw new IntegrationMisconfiguredException('errors.api.bluesky.not_configured', 'bluesky_not_configured');
        }

        if ($this->config->appPassword() === '') {
            throw new IntegrationMisconfiguredException('errors.api.bluesky.not_configured', 'bluesky_not_configured');
        }

        if (! $this->isValidBaseUrl($this->config->pdsUrl())) {
            throw new IntegrationMisconfiguredException('errors.api.bluesky.invalid_base_url', 'bluesky_invalid_base_url');
        }
    }

    private function isValidBaseUrl(string $baseUrl): bool
    {
        if ($baseUrl === '') {
            return false;
        }

        $parts = parse_url($baseUrl);

        if ($parts === false) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        return in_array($scheme, ['http', 'https'], true) && $host !== '';
    }
}
