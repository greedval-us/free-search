<?php

namespace App\Modules\Gdelt\Search;

use App\Modules\Gdelt\Core\Contracts\GdeltGatewayInterface;
use App\Modules\Gdelt\DTO\Request\GdeltSearchQueryDTO;
use App\Modules\Gdelt\DTO\Result\GdeltSearchResultDTO;
use App\Modules\Gdelt\Search\Contracts\GdeltSearchApplicationServiceInterface;
use Illuminate\Support\Facades\Log;

class GdeltSearchApplicationService implements GdeltSearchApplicationServiceInterface
{
    public function __construct(
        private readonly GdeltGatewayInterface $gateway,
    ) {
    }

    public function search(GdeltSearchQueryDTO $query): GdeltSearchResultDTO
    {
        try {
            $payload = $this->gateway->searchArticles($query);
            $items = $this->mapArticles($payload);

            return new GdeltSearchResultDTO(
                ok: true,
                items: $items,
                total: count($items),
            );
        } catch (\Throwable $e) {
            Log::warning('[GdeltSearchApplicationService::search] Failed to search articles', [
                'query' => $query->query,
                'error' => $e->getMessage(),
            ]);

            $message = __('Failed to load data from GDELT API.');

            if ((int) $e->getCode() === 429 || str_contains($e->getMessage(), 'status 429')) {
                $message = __('GDELT API rate limit reached. Please wait 30-60 seconds and try again.');
            } elseif (str_contains(strtolower($e->getMessage()), 'timed out')) {
                $message = __('GDELT API timeout. Please try again in a moment.');
            } elseif (str_contains(strtolower($e->getMessage()), 'could not resolve host')) {
                $message = __('Network error while contacting GDELT API. Please check your internet connection.');
            }

            return new GdeltSearchResultDTO(
                ok: false,
                items: [],
                total: 0,
                message: $message,
            );
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    private function mapArticles(array $payload): array
    {
        $articles = is_array($payload['articles'] ?? null) ? $payload['articles'] : [];
        $result = [];

        foreach ($articles as $article) {
            if (!is_array($article)) {
                continue;
            }

            $result[] = [
                'title' => (string) ($article['title'] ?? ''),
                'url' => (string) ($article['url'] ?? ''),
                'domain' => (string) ($article['domain'] ?? ''),
                'language' => (string) ($article['language'] ?? ''),
                'sourceCountry' => (string) ($article['sourcecountry'] ?? ''),
                'sourceCommonName' => (string) ($article['sourcecommonname'] ?? ''),
                'seenDate' => (string) ($article['seendate'] ?? ''),
                'socialImage' => (string) ($article['socialimage'] ?? ''),
                'tone' => $article['tone'] ?? null,
            ];
        }

        return $result;
    }
}
