<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use App\Modules\Fio\Infrastructure\Parsers\FioBingRssResultParser;

final class FioYahooRssSearchProvider extends AbstractFioHttpSearchProvider implements FioPublicSearchProviderInterface
{
    private const RSS_ACCEPT_HEADER = 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8';

    public function __construct(
        private readonly FioBingRssResultParser $resultParser,
        FioQualifierLexicon $qualifierLexicon,
    ) {
        parent::__construct($qualifierLexicon);
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName, ?string $qualifier = null): array
    {
        $query = $this->buildQuery($fullName, $qualifier, 2);
        $url = 'https://search.yahoo.com/rss?p=' . urlencode($query);

        $items = $this->resultParser->parse($this->fetch($url, [
            'Accept' => self::RSS_ACCEPT_HEADER,
        ]));

        // Normalize parser source label from "bing" to "yahoo" for this provider.
        return array_map(
            static fn (PublicSearchEntryDTO $item): PublicSearchEntryDTO => new PublicSearchEntryDTO(
                title: $item->title,
                snippet: $item->snippet,
                url: $item->url,
                domain: $item->domain,
                source: 'yahoo',
            ),
            $items
        );
    }

}
