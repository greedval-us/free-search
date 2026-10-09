<?php

namespace App\Integrations\TelegramBot;

use App\Models\SiteIntelScheduledReport;
use App\Modules\SiteIntel\Application\Reports\Events\SiteIntelReportCompleted;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Services\Access\SiteIntelReportAccess;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class SiteIntelReportBotListener
{
    public function __construct(private BotConfig $config, private DeliveryOutbox $outbox, private SiteIntelReportAccess $access) {}

    public function handle(SiteIntelReportCompleted $event): void
    {
        if (! $this->config->active()) {
            return;
        }

        try {
            $report = SiteIntelScheduledReport::query()->with(['schedule', 'user'])
                ->where('status', SiteIntelScheduledReport::COMPLETED)
                ->find($event->reportId, ['id', 'user_id', 'schedule_id', 'is_manual', 'report_type']);
            if ($report === null || $report->schedule === null || $report->schedule->trashed()
                || (! $report->schedule->enabled && ! $report->is_manual) || ! $report->schedule->send_to_bot
                || $report->schedule->user_id !== $report->user_id || $report->user === null
                || ! $this->access->allows($report->user, $report->report_type)) {
                return;
            }
            $link = BotLink::query()->where('user_id', $report->user_id)->first();
            if ($link !== null) {
                $this->outbox->enqueue($link, 'site_intel_report', (string) $report->id, ['format' => 'html']);
            }
        } catch (Throwable $exception) {
            // Report generation remains available when this optional delivery channel fails.
            Log::warning('Site Intel report delivery unavailable.', ['report_id' => $event->reportId, 'exception_class' => $exception::class]);
            throw $exception;
        }
    }
}
