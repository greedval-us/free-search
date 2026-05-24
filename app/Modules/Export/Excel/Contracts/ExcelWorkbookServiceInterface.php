<?php

namespace App\Modules\Export\Excel\Contracts;

use App\Modules\Export\Excel\SheetDefinition;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface ExcelWorkbookServiceInterface
{
    /**
     * @param array<int, SheetDefinition> $definitions
     */
    public function download(string $filename, array $definitions): BinaryFileResponse;
}

