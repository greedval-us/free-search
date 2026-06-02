<?php

namespace App\Modules\Mastodon\Parser\Contracts;

use App\Modules\Export\Excel\SheetDefinition;

interface MastodonParserExportBuilderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<int, SheetDefinition>
     */
    public function buildSheets(array $payload): array;
}
