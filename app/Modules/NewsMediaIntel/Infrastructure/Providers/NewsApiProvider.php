<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class NewsApiProvider implements NewsFeedProviderInterface
{
    /**
     * @return array<int, NewsMentionDTO>
     */
    public function fetch(string $query): array
    {
        $apiKey = (string) config('osint.news_media_intel.newsapi.api_key', '');
        if (trim($apiKey) === '') {
            return [];
        }

        $baseUrl = (string) config('osint.news_media_intel.newsapi.base_url', 'https://newsapi.org/v2/everything');
        $language = (string) config('osint.news_media_intel.newsapi.language', 'ru');
        $pageSize = (int) config('osint.news_media_intel.newsapi.page_size', 30);
        $timeoutSeconds = (int) config('osint.news_media_intel.newsapi.timeout_seconds', 15);

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
                source: 'newsapi',
                title: $title,
                snippet: $snippet,
                link: $link,
                publishedAt: $publishedAt,
            );
        }

        return $items;
    }
}
