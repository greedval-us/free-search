<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use App\Modules\Fio\Infrastructure\Parsers\FioRedditRssResultParser;

final class FioRedditRssSearchProvider extends AbstractFioHttpSearchProvider implements FioPublicSearchProviderInterface
{
    private const RSS_ACCEPT_HEADER = 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8';

    public function __construct(
        private readonly FioRedditRssResultParser $resultParser,
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
        $url = 'https://www.reddit.com/search.rss?q=' . urlencode($query);

        return $this->resultParser->parse($this->fetch($url, [
            'Accept' => self::RSS_ACCEPT_HEADER,
        ]));
    }
}
