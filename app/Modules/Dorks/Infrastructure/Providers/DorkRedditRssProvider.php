<?php

namespace App\Modules\Dorks\Infrastructure\Providers;

use App\Modules\Dorks\Domain\Contracts\DorkSourceProviderInterface;
use App\Modules\Dorks\Infrastructure\Parsers\DorkRedditRssParser;

final class DorkRedditRssProvider extends AbstractDorkHttpProvider implements DorkSourceProviderInterface
{
    public function __construct(
        private readonly DorkRedditRssParser $parser,
    ) {
    }

    public function search(string $dorkQuery, string $goal, int $limit): array
    {
        $url = 'https://www.reddit.com/search.rss?q=' . urlencode($dorkQuery);
        $xml = $this->fetch($url, [
            'Accept' => 'application/rss+xml,application/xml,text/xml',
        ]);

        return $this->parser->parse($xml, $goal, $dorkQuery, $limit);
    }
}

