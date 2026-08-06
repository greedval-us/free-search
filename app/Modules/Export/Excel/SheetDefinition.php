<?php

namespace App\Modules\Export\Excel;

final readonly class SheetDefinition
{
    /**
     * @param array<int, string> $headings
     * @param array<int, array<int, mixed>> $rows
     * @param array<string, string> $columnFormats
     * @param array<string, float|int> $columnWidths
     * @param array<int, string> $hyperlinkColumns
     * @param array<int, string> $centeredColumns
     */
    public function __construct(
        public string $title,
        public array $headings,
        public array $rows,
        public array $columnFormats = [],
        public array $columnWidths = [],
        public array $hyperlinkColumns = [],
        public array $centeredColumns = [],
    ) {
    }
}
