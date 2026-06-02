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

    public function accountStatuses(string $accountId, int $limit): array
    {
        return $this->get("/api/v1/accounts/{$accountId}/statuses", [
            'limit' => $limit,
        ]);
    }

    public function accountFollowers(string $accountId, int $limit): array
    {
        return $this->get("/api/v1/accounts/{$accountId}/followers", [
            'limit' => $limit,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query): array
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

        return $response->json() ?? [];
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
