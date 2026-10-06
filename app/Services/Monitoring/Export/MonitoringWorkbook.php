<?php

namespace App\Services\Monitoring\Export;

use App\Models\MonitoringReport;
use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\Export\Excel\StyledArraySheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final readonly class MonitoringWorkbook implements Export, WithMultipleSheets
{
    public function __construct(private MonitoringReport $report) {}

    public function sheets(): array
    {
        $ru = ($this->report->configuration['language'] ?? 'en') === 'ru';
        $summary = $this->report->summary;
        $rows = [[$ru ? 'Проект' : 'Project', $summary['title']], [$ru ? 'Версия' : 'Version', $this->report->version],
            [$ru ? 'Период от (UTC)' : 'Period start (UTC)', $this->report->start_at->toISOString()],
            [$ru ? 'Период до (UTC), исключая' : 'Period end (UTC), exclusive', $this->report->end_at->toISOString()],
            [$ru ? 'Часовой пояс' : 'Timezone', $this->report->timezone], [$ru ? 'Состояние' : 'Status', $this->report->status],
            [$ru ? 'Материалов' : 'Publications', $summary['count']], [$ru ? 'Сводка' : 'Summary', $summary['introduction']],
            [$ru ? 'Покрытие' : 'Coverage', json_encode($this->report->coverage, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]];
        foreach ($summary['bullets'] ?? [] as $bullet) {
            $rows[] = [$bullet['text'], implode("\n", $bullet['urls'])];
        }

        return [new StyledArraySheet(new SheetDefinition(title: $ru ? 'Сводка' : 'Summary', headings: [$ru ? 'Поле' : 'Field', $ru ? 'Значение' : 'Value'],
            rows: $rows, columnWidths: ['A' => 35, 'B' => 90])), new MonitoringItemsSheet($this->report->id, $ru)];
    }
}
