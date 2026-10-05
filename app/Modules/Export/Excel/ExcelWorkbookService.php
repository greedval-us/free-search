<?php

namespace App\Modules\Export\Excel;

use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Support\Http\DocumentResponseHeaders;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelWorkbookService implements ExcelWorkbookServiceInterface
{
    /**
     * @param  array<int, SheetDefinition>  $definitions
     */
    public function download(string $filename, array $definitions): BinaryFileResponse
    {
        return Excel::download(new WorkbookExport($definitions), $filename, headers: DocumentResponseHeaders::download());
    }
}
