<?php

namespace App\Services\Monitoring\Export;

use App\Models\MonitoringReportItem;
use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\Export\Excel\StyledSheet;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;

final class MonitoringItemsSheet extends StyledSheet implements FromQuery, WithMapping
{
    public function __construct(private readonly int $reportId, bool $ru)
    {
        parent::__construct(new SheetDefinition(title: $ru ? 'Материалы' : 'Publications', headings: $ru
            ? ['Площадка', 'Источник', 'Внешний ID', 'Автор', 'Заголовок', 'Текст', 'Дата публикации', 'Получено', 'URL', 'Метрики']
            : ['Platform', 'Source', 'External ID', 'Author', 'Title', 'Text', 'Published at', 'Collected at', 'URL', 'Metrics'], rows: [],
            columnWidths: ['A' => 15, 'B' => 25, 'C' => 30, 'D' => 24, 'E' => 45, 'F' => 75, 'G' => 28, 'H' => 28, 'I' => 45, 'J' => 30], hyperlinkColumns: ['I']));
    }

    public function query(): Builder
    {
        return MonitoringReportItem::query()->where('report_id', $this->reportId)->orderBy('id');
    }

    public function map(mixed $row): array
    {
        $s = $row->snapshot;

        return [$s['platform'], $s['source_title'], $s['external_id'], $s['author'], $s['title'], $s['text'],
            $s['published_at'], $s['collected_at'], $s['url'], json_encode($s['metrics'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
    }
}
