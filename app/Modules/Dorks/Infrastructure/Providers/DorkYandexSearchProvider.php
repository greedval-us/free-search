<?php

namespace App\Modules\Dorks\Infrastructure\Providers;

use App\Modules\Dorks\Domain\Contracts\DorkSourceProviderInterface;
use App\Modules\Dorks\Infrastructure\Parsers\DorkYandexSearchParser;

final class DorkYandexSearchProvider extends AbstractDorkHttpProvider implements DorkSourceProviderInterface
{
    public function __construct(
        private readonly DorkYandexSearchParser $parser,
    ) {
    }

    public function search(string $dorkQuery, string $goal, int $limit): array
    {
        $url = 'https://yandex.com/search/?text=' . urlencode($dorkQuery);
        $html = $this->fetch($url);

        return $this->parser->parse($html, $goal, $dorkQuery, $limit);
    }
}

