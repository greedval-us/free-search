<?php

namespace App\Modules\Dorks\Infrastructure\Providers;

use App\Modules\Dorks\Domain\Contracts\DorkSourceProviderInterface;
use App\Modules\Dorks\Infrastructure\Parsers\DorkBingRssParser;

final class DorkBingRssProvider extends AbstractDorkHttpProvider implements DorkSourceProviderInterface
{
    public function __construct(
        private readonly DorkBingRssParser $parser,
    ) {
    }

    public function search(string $dorkQuery, string $goal, int $limit): array
    {
        $url = 'https://www.bing.com/search?format=rss&q=' . urlencode($dorkQuery);
        $xml = $this->fetch($url);

        return $this->parser->parse($xml, $goal, $dorkQuery, $limit);
    }
}

