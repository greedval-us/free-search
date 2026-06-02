<?php

namespace App\Modules\Mastodon;

use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\Support\MastodonApiConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MastodonApiClient implements MastodonGatewayInterface
{
    public function __construct(
        private readonly MastodonApiConfig $config,
    ) {
    }

    public function search(array $params): array
    {
        return $this->get('/api/v2/search', $params);
    }

    public function context(string $statusId): array
    {
        return $this->get("/api/v1/statuses/{$statusId}/context", []);
    }

    public function accountStatuses(string $accountId, int $limit, ?string $maxId = null): array
    {
        return $this->getPaginatedCollection("/api/v1/accounts/{$accountId}/statuses", array_filter([
            'limit' => $limit,
            'max_id' => $maxId,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public function accountFollowers(string $accountId, int $limit, ?string $maxId = null): array
    {
        return $this->getPaginatedCollection("/api/v1/accounts/{$accountId}/followers", array_filter([
            'limit' => $limit,
            'max_id' => $maxId,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public function tagTimeline(string $tagName, int $limit, ?string $maxId = null): array
    {
        $encodedTagName = rawurlencode($tagName);

        return $this->getPaginatedCollection("/api/v1/timelines/tag/{$encodedTagName}", array_filter([
            'limit' => $limit,
            'max_id' => $maxId,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query): array
    {
        $response = $this->request($endpoint, $query);

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function getPaginatedCollection(string $endpoint, array $query): array
    {
        $response = $this->request($endpoint, $query);

        return [
            'items' => $response->json() ?? [],
            'pagination' => [
                'nextMaxId' => $this->extractNextMaxId((string) $response->header('Link', '')),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function request(string $endpoint, array $query): \Illuminate\Http\Client\Response
    {
        if ($this->config->apiToken() === '') {
            throw new RuntimeException('MASTODON_API_TOKEN is not configured.');
        }

        $baseUrl = $this->config->baseUrl();

        if (! $this->isValidBaseUrl($baseUrl)) {
            throw new RuntimeException(
                sprintf(
                    'MASTODON_API_BASE_URL must be a valid http(s) Mastodon instance URL, got "%s".',
                    $baseUrl
                )
            );
        }

        try {
            $response = $this->http()->get($endpoint, $query);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to Mastodon API. Check instance URL and network access.', 503, $exception);
        }

        if ($response->failed()) {
            $message = (string) Arr::get($response->json(), 'error', 'Mastodon API request failed.');

            throw new RuntimeException($message, $response->status());
        }

        return $response;
    }

    private function extractNextMaxId(string $linkHeader): ?string
    {
        if ($linkHeader === '') {
            return null;
        }

        foreach (explode(',', $linkHeader) as $part) {
            if (! str_contains($part, 'rel="next"')) {
                continue;
            }

            if (! preg_match('/<([^>]+)>/', $part, $matches)) {
                continue;
            }

            $url = $matches[1] ?? '';

            if ($url === '') {
                continue;
            }

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $maxId = trim((string) ($query['max_id'] ?? ''));

            return $maxId !== '' ? $maxId : null;
        }

        return null;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->config->baseUrl())
            ->acceptJson()
            ->withToken($this->config->apiToken())
            ->timeout($this->config->timeoutSeconds())
            ->retry(
                $this->config->retryAttempts(),
                $this->config->retryDelayMilliseconds(),
                throw: false
            );
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
