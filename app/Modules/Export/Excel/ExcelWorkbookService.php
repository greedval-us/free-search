<?php

namespace App\Modules\Export\Excel;

use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelWorkbookService
{
    /**
     * @param array<int, SheetDefinition> $definitions
     */
    public function download(string $filename, array $definitions): BinaryFileResponse
    {
        return Excel::download(new WorkbookExport($definitions), $filename);
    }
}

