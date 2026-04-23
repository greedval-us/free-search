<?php

namespace App\Modules\Dorks\Infrastructure\Providers;

use App\Modules\Dorks\Domain\Contracts\DorkSourceProviderInterface;
use App\Modules\Dorks\Infrastructure\Parsers\DorkYahooSearchParser;

final class DorkYahooSearchProvider extends AbstractDorkHttpProvider implements DorkSourceProviderInterface
{
    public function __construct(
        private readonly DorkYahooSearchParser $parser,
    ) {
    }

    public function search(string $dorkQuery, string $goal, int $limit): array
    {
        $url = 'https://search.yahoo.com/search?p=' . urlencode($dorkQuery);
        $html = $this->fetch($url);

        return $this->parser->parse($html, $goal, $dorkQuery, $limit);
    }
}

