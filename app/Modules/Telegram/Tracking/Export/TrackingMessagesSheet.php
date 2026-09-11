<?php

namespace App\Modules\Telegram\Tracking\Export;

use App\Models\TelegramTrackingMessage;
use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\Export\Excel\StyledSheet;
use App\Modules\Telegram\Tracking\TrackingPresenter;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

final class TrackingMessagesSheet extends StyledSheet implements FromQuery, WithMapping
{
    public function __construct(private readonly int $trackingId, private readonly int $snapshotId)
    {
        parent::__construct(new SheetDefinition(
            title: __('telegram_tracking.export.messages'),
            headings: array_values(__('telegram_tracking.export.columns')),
            rows: [], columnWidths: ['A' => 25, 'B' => 24, 'C' => 20, 'D' => 20, 'E' => 70, 'F' => 28, 'G' => 28, 'H' => 45],
            hyperlinkColumns: ['H'],
        ));
    }

    public function query(): Builder
    {
        return TelegramTrackingMessage::query()->where('tracking_id', $this->trackingId)->where('id', '<=', $this->snapshotId)->with('source')->orderBy('id');
    }

    public function map(mixed $row): array
    {
        $data = TrackingPresenter::message($row);

        return [$data['group'], $data['peer_id'], $data['message_id'], $data['sender_id'], $data['text'], $data['sent_at'], $data['received_at'], $data['url']];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (in_array($cell->getColumn(), ['B', 'C', 'D'], true) && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
