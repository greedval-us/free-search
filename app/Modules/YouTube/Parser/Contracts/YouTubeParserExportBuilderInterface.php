<?php

namespace App\Modules\YouTube\Parser\Contracts;

use App\Modules\Export\Excel\SheetDefinition;

interface YouTubeParserExportBuilderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<int, SheetDefinition>
     */
    public function buildSheets(array $payload): array;
}

