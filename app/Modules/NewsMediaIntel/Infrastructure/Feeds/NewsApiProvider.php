<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Enums\NewsFeedSource;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class NewsApiProvider implements NewsFeedProviderInterface
{
    public function __construct(
        private readonly NewsMediaIntelConfig $config,
    ) {
    }

    public function key(): string
    {
        return NewsFeedSource::NewsApi->value;
    }

    /**
     * @return array<int, NewsMentionDTO>
     */
    public function fetch(string $query): array
    {
        $apiKey = $this->config->newsApiKey();
        if (trim($apiKey) === '') {
            return [];
        }

        $baseUrl = $this->config->newsApiBaseUrl();
        $language = $this->config->newsApiLanguage();
        $pageSize = $this->config->newsApiPageSize();
        $timeoutSeconds = $this->config->newsApiTimeoutSeconds();

        try {
            $response = Http::timeout($timeoutSeconds)->get($baseUrl, [
                'apiKey' => $apiKey,
                'q' => $query,
                'language' => $language,
                'sortBy' => 'publishedAt',
                'pageSize' => $pageSize,
            ]);
        } catch (ConnectionException) {
            return [];
        }

        if (!$response->ok()) {
            return [];
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return [];
        }

        $articles = $payload['articles'] ?? [];
        if (!is_array($articles)) {
            return [];
        }

        $items = [];
        foreach ($articles as $article) {
            if (!is_array($article)) {
                continue;
            }

            $title = trim((string) ($article['title'] ?? ''));
            $snippet = trim((string) ($article['description'] ?? ''));
            $link = trim((string) ($article['url'] ?? ''));
            $publishedAt = trim((string) ($article['publishedAt'] ?? ''));

            if ($title === '' || $link === '') {
                continue;
            }

            $items[] = new NewsMentionDTO(
                source: NewsFeedSource::NewsApi->value,
                title: $title,
                snippet: $snippet,
                link: $link,
                publishedAt: $publishedAt,
            );
        }

        return $items;
    }
}
