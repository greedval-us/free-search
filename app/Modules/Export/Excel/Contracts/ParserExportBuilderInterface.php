<?php

namespace App\Modules\Export\Excel\Contracts;

use App\Modules\Export\Excel\SheetDefinition;

interface ParserExportBuilderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<int, SheetDefinition>
     */
    public function buildSheets(array $payload): array;
}
