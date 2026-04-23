<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use App\Modules\Fio\Domain\Services\FioQualifierLexicon;
use App\Modules\Fio\Infrastructure\Parsers\FioDuckDuckGoResultParser;

final class FioDuckDuckGoSearchProvider extends AbstractFioHttpSearchProvider implements FioPublicSearchProviderInterface
{
    public function __construct(
        private readonly FioDuckDuckGoResultParser $resultParser,
        FioQualifierLexicon $qualifierLexicon,
    ) {
        parent::__construct($qualifierLexicon);
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName, ?string $qualifier = null): array
    {
        $query = $this->buildQuery($fullName, $qualifier, 3);
        $url = 'https://html.duckduckgo.com/html/?q=' . urlencode($query);

        return $this->resultParser->parse($this->fetch($url));
    }
}
