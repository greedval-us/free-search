<?php

namespace App\Modules\Export\Excel;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkbookExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<int, SheetDefinition>  $definitions
     */
    public function __construct(
        private readonly array $definitions,
    ) {}

    public function sheets(): array
    {
        return array_map(
            static fn (SheetDefinition $definition): StyledArraySheet => new StyledArraySheet($definition),
            $this->definitions
        );
    }
}
