<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use App\Modules\Fio\Infrastructure\Parsers\FioDuckDuckGoResultParser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class FioDuckDuckGoSearchProvider implements FioPublicSearchProviderInterface
{
    public function __construct(
        private readonly FioDuckDuckGoResultParser $resultParser,
        private readonly FioQualifierLexicon $qualifierLexicon,
    ) {
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName, ?string $qualifier = null): array
    {
        $query = $this->buildQuery($fullName, $qualifier);
        $url = 'https://html.duckduckgo.com/html/?q=' . urlencode($query);

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
        $parts = [sprintf('"%s"', $fullName)];

        $terms = $this->qualifierLexicon->queryTerms($qualifier, 3);
        if (count($terms) > 0) {
            $quoted = array_map(static fn (string $term): string => '"' . $term . '"', $terms);
            $parts[] = '(' . implode(' OR ', $quoted) . ')';
        }

        return implode(' ', $parts);
    }
}
