<?php

namespace App\Modules\Export\Excel;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StyledArraySheet extends SafeSpreadsheetValueBinder implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly SheetDefinition $definition,
    ) {}

    public function title(): string
    {
        return $this->definition->title;
    }

    public function headings(): array
    {
        return $this->definition->headings;
    }

    public function array(): array
    {
        return $this->definition->rows;
    }

    public function columnFormats(): array
    {
        return $this->definition->columnFormats;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = max(1, $sheet->getHighestRow());
                $fullRange = "A1:{$highestColumn}{$highestRow}";

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$highestColumn}1");
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getDefaultRowDimension()->setRowHeight(-1);

                foreach ($this->definition->columnWidths as $column => $width) {
                    $sheet->getColumnDimension($column)->setAutoSize(false);
                    $sheet->getColumnDimension($column)->setWidth((float) $width);
                }

                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => 'E9EEF7'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'C7D1E0'],
                        ],
                    ],
                ]);

                if ($highestRow > 1) {
                    for ($row = 2; $row <= $highestRow; $row++) {
                        if ($row % 2 !== 0) {
                            continue;
                        }

                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'color' => ['rgb' => 'F8FAFD'],
                            ],
                        ]);
                    }
                }

                $sheet->getStyle($fullRange)->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E3E8F1'],
                        ],
                    ],
                ]);

                foreach ($this->definition->centeredColumns as $column) {
                    $sheet->getStyle("{$column}2:{$column}{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->definition->hyperlinkColumns as $column) {
                    $columnIndex = Coordinate::columnIndexFromString($column);

                    for ($row = 2; $row <= $highestRow; $row++) {
                        $cell = $sheet->getCell([$columnIndex, $row]);
                        $value = trim((string) $cell->getValue());

                        if ($value === '' || ! filter_var($value, FILTER_VALIDATE_URL)) {
                            continue;
                        }

                        $cell->getHyperlink()->setUrl($value);
                        $sheet->getStyle("{$column}{$row}")->applyFromArray([
                            'font' => [
                                'color' => ['rgb' => Color::COLOR_BLUE],
                                'underline' => true,
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}
