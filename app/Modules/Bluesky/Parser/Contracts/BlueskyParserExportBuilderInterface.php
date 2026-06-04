<?php

namespace App\Modules\Bluesky\Parser\Contracts;

use App\Modules\Export\Excel\SheetDefinition;

interface BlueskyParserExportBuilderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<int, SheetDefinition>
     */
    public function buildSheets(array $payload): array;
}
