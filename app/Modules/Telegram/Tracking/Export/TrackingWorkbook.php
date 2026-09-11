<?php

namespace App\Modules\Telegram\Tracking\Export;

use App\Models\TelegramTracking;
use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\Export\Excel\StyledArraySheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final readonly class TrackingWorkbook implements Export, WithMultipleSheets
{
    public function __construct(private TelegramTracking $task, private int $snapshotId) {}

    public function sheets(): array
    {
        return [new StyledArraySheet(new SheetDefinition(
            title: __('telegram_tracking.export.summary'),
            headings: [__('telegram_tracking.export.field'), __('telegram_tracking.export.value')],
            rows: [
                [__('telegram_tracking.export.name'), $this->task->name],
                [__('telegram_tracking.export.query'), $this->task->query],
                [__('telegram_tracking.export.started'), $this->task->started_at->toIso8601String()],
                [__('telegram_tracking.export.expires'), $this->task->expires_at->toIso8601String()],
                [__('telegram_tracking.export.generated'), now()->toIso8601String()],
            ], columnWidths: ['A' => 28, 'B' => 65],
        )), new TrackingMessagesSheet($this->task->id, $this->snapshotId)];
    }
}
