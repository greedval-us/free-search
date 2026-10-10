<?php

namespace App\Modules\Export\Excel;

use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\Export\ParserExportBudget;
use App\Support\Http\DocumentResponseHeaders;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ExcelWorkbookService implements ExcelWorkbookServiceInterface
{
    public function __construct(private readonly ParserExportBudget $budget) {}

    /**
     * @param  array<int, SheetDefinition>  $definitions
     */
    public function download(string $filename, array $definitions): BinaryFileResponse
    {
        $this->budget->assertSheetsFit($definitions);
        $response = Excel::download(new WorkbookExport($definitions), $filename, headers: DocumentResponseHeaders::download());
        try {
            $this->budget->assertBytes($response->getFile()->getSize());

            return $response;
        } catch (Throwable $exception) {
            @unlink($response->getFile()->getPathname());

            throw $exception;
        }
    }
}
