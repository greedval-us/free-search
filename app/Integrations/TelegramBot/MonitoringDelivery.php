<?php

namespace App\Integrations\TelegramBot;

use App\Models\MonitoringReport;
use App\Modules\TelegramBot\Application\BotAccess;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;

final readonly class MonitoringDelivery
{
    public function __construct(private DeliveryOutbox $outbox, private BotAccess $access, private MonitoringDigestProvider $digests) {}

    public function enqueue(MonitoringReport $report): void
    {
        $report->refresh();
        if (! in_array($report->status, ['completed', 'partial', 'empty'], true) || $report->file_status !== 'ready') {
            return;
        }
        // Enabling delivery later must not replay already finalized historical reports.
        if (in_array($report->delivery_status, ['sent', 'skipped', 'failed', 'disabled', 'not_linked'], true)) {
            return;
        }
        $link = BotLink::query()->with(['user', 'chat'])->where('user_id', $report->user_id)->first();
        if ($link === null) {
            $report->update(['delivery_status' => 'not_linked']);

            return;
        }
        $payload = ['version' => (int) $report->version, 'generation' => $report->configuration['generation'] ?? 0,
            'schedule_generation' => $report->configuration['schedule_generation'] ?? null];
        $candidate = new BotDelivery(['kind' => 'monitoring_digest', 'reference' => (string) $report->id, 'payload' => $payload, 'automatic' => true]);
        if (! $this->access->allowsDelivery($link, 'monitoring_digest', true) || ! $this->digests->allows($candidate, $link)) {
            $report->update(['delivery_status' => 'disabled']);

            return;
        }
        $this->outbox->enqueue($link, 'monitoring_digest', (string) $report->id, $payload);
        $delivery = BotDelivery::query()->where('link_id', $link->id)->where('kind', 'monitoring_digest')
            ->where('reference', (string) $report->id)->where('automatic', true)->first();
        $report->update(['delivery_status' => $delivery?->status ?? BotDelivery::PENDING]);
        if ($report->project->attach_files && $this->access->allowsDelivery($link, 'monitoring_document', true)) {
            $this->outbox->enqueue($link, 'monitoring_document', (string) $report->id, [...$payload, 'format' => 'xlsx']);
        }
    }
}
