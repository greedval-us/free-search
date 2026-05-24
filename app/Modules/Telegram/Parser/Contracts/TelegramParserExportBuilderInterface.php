<?php

namespace App\Modules\Telegram\Parser\Contracts;

use App\Modules\Export\Excel\SheetDefinition;

interface TelegramParserExportBuilderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<int, SheetDefinition>
     */
    public function buildSheets(array $payload): array;
}

