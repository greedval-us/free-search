<?php

namespace App\Modules\Bluesky;

use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use App\Modules\Bluesky\Support\BlueskyApiConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

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
            throw new RuntimeException('Could not connect to Bluesky API. Check PDS URL and network access.', 503, $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException($this->resolveErrorMessage($response->json()), $response->status());
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
            throw new RuntimeException('Could not create Bluesky session. Check PDS URL and network access.', 503, $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException($this->resolveErrorMessage($response->json(), 'Bluesky authentication failed.'), $response->status());
        }

        $session = $response->json() ?? [];
        $accessJwt = trim((string) ($session['accessJwt'] ?? ''));

        if ($accessJwt === '') {
            throw new RuntimeException('Bluesky session response did not contain an access token.');
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
            throw new RuntimeException('BLUESKY_IDENTIFIER is not configured.');
        }

        if ($this->config->appPassword() === '') {
            throw new RuntimeException('BLUESKY_APP_PASSWORD is not configured.');
        }

        if (! $this->isValidBaseUrl($this->config->pdsUrl())) {
            throw new RuntimeException(sprintf(
                'BLUESKY_PDS_URL must be a valid http(s) URL, got "%s".',
                $this->config->pdsUrl()
            ));
        }
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function resolveErrorMessage(?array $payload, string $fallback = 'Bluesky API request failed.'): string
    {
        return trim((string) (
            Arr::get($payload, 'message')
            ?: Arr::get($payload, 'error')
            ?: $fallback
        ));
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
