<?php

namespace App\Modules\Mastodon;

use App\Exceptions\PublicException;
use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\Support\MastodonApiConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

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

    public function lookupAccount(string $acct): array
    {
        return $this->get('/api/v1/accounts/lookup', [
            'acct' => $acct,
        ]);
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
            throw new PublicException('errors.api.mastodon.not_configured', 503, 'mastodon_not_configured');
        }

        $baseUrl = $this->config->baseUrl();

        if (! $this->isValidBaseUrl($baseUrl)) {
            throw new PublicException('errors.api.mastodon.invalid_base_url', 503, 'mastodon_invalid_base_url');
        }

        try {
            $response = $this->http()->get($endpoint, $query);
        } catch (ConnectionException $exception) {
            throw new PublicException(
                'errors.api.mastodon.unavailable',
                503,
                'mastodon_unavailable',
                previous: $exception,
            );
        }

        if ($response->failed()) {
            $status = $response->status();

            throw new PublicException(
                $status === 429 ? 'errors.api.mastodon.rate_limited' : 'errors.api.mastodon.request_failed',
                $status,
                $status === 429 ? 'mastodon_rate_limited' : 'mastodon_request_failed',
            );
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
