<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Application\Support\FioHttpConfig;
use App\Modules\Fio\Application\Support\FioSearchConfig;
use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use App\Modules\Fio\Infrastructure\Parsers\FioDuckDuckGoResultParser;

final class FioDuckDuckGoSearchProvider extends AbstractFioHttpSearchProvider implements FioPublicSearchProviderInterface
{
    public function __construct(
        private readonly FioDuckDuckGoResultParser $resultParser,
        FioQualifierLexicon $qualifierLexicon,
        FioHttpConfig $httpConfig,
        FioSearchConfig $searchConfig,
    ) {
        parent::__construct($qualifierLexicon, $httpConfig, $searchConfig);
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName, ?string $qualifier = null): array
    {
        $queries = $this->queryVariants($fullName, $qualifier, 3, 'web');
        $entries = [];

        foreach ($queries as $query) {
            $url = 'https://html.duckduckgo.com/html/?q=' . urlencode($query);
            $entries = [...$entries, ...$this->resultParser->parse($this->fetch($url))];
        }

        return $this->deduplicateEntries($entries);
    }
}
