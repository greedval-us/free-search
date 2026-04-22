<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use App\Modules\Fio\Infrastructure\Parsers\FioGoogleNewsRssResultParser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class FioGoogleNewsRssSearchProvider implements FioPublicSearchProviderInterface
{
    public function __construct(
        private readonly FioGoogleNewsRssResultParser $resultParser,
        private readonly FioQualifierLexicon $qualifierLexicon,
    ) {
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName, ?string $qualifier = null): array
    {
        $query = $this->buildQuery($fullName, $qualifier);
        $url = 'https://news.google.com/rss/search?q=' . urlencode($query) . '&hl=en-US&gl=US&ceid=US:en';

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

    private function buildQuery(string $fullName, ?string $qualifier): string
    {
        $parts = ['"' . $fullName . '"'];

        $terms = $this->qualifierLexicon->queryTerms($qualifier, 2);
        if (count($terms) > 0) {
            $quoted = array_map(static fn (string $term): string => '"' . $term . '"', $terms);
            $parts[] = '(' . implode(' OR ', $quoted) . ')';
        }

        return implode(' ', $parts);
    }
}
