<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use App\Modules\Fio\Infrastructure\Parsers\FioBingRssResultParser;

final class FioBingRssSearchProvider extends AbstractFioHttpSearchProvider implements FioPublicSearchProviderInterface
{
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
        $queries = $this->queryVariants($fullName, $qualifier, 3, 'web');
        $entries = [];

        foreach ($queries as $query) {
            $url = 'https://www.bing.com/search?format=rss&q=' . urlencode($query);
            $entries = [...$entries, ...$this->resultParser->parse($this->fetch($url))];
        }

        return $this->deduplicateEntries($entries);
    }
}
