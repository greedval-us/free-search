<?php

namespace App\Modules\Export\Excel;

final readonly class SheetDefinition
{
    /**
     * @param array<int, string> $headings
     * @param array<int, array<int, mixed>> $rows
     * @param array<string, string> $columnFormats
     */
    public function __construct(
        public string $title,
        public array $headings,
        public array $rows,
        public array $columnFormats = [],
    ) {
    }
}

