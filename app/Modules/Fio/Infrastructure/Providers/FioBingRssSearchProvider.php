<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Infrastructure\Parsers\FioBingRssResultParser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class FioBingRssSearchProvider implements FioPublicSearchProviderInterface
{
    public function __construct(
        private readonly FioBingRssResultParser $resultParser,
    ) {
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName): array
    {
        $query = '"' . $fullName . '"';
        $url = 'https://www.bing.com/search?format=rss&q=' . urlencode($query);

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'FreeSearch-FIO/1.0',
            ])
                ->timeout(12)
                ->retry(1, 250)
                ->get($url);
        } catch (ConnectionException) {
            throw new RuntimeException(__('Public search provider is temporarily unavailable. Try again.'));
        }

        if (!$response->ok()) {
            throw new RuntimeException(__('Unable to fetch public matches now. Try again later.'));
        }

        return $this->resultParser->parse((string) $response->body());
    }
}
