<?php

namespace App\Integrations\TelegramBot;

use App\Models\NewsMediaScheduledReport;
use App\Modules\NewsMediaIntel\Application\Reports\Events\NewsMediaReportCompleted;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class NewsMediaReportBotListener
{
    public function __construct(private BotConfig $config, private DeliveryOutbox $outbox) {}

    public function handle(NewsMediaReportCompleted $event): void
    {
        if (! $this->config->active()) {
            return;
        }

        try {
            $report = NewsMediaScheduledReport::query()->with(['schedule', 'user'])
                ->where('status', NewsMediaScheduledReport::COMPLETED)->whereNotNull('data')
                ->find($event->reportId, ['id', 'user_id', 'schedule_id', 'is_manual']);
            if ($report === null || $report->schedule === null || $report->schedule->trashed()
                || (! $report->schedule->enabled && ! $report->is_manual) || ! $report->schedule->send_to_bot
                || $report->schedule->user_id !== $report->user_id || $report->user === null
                || $report->user->isBlocked() || ! $report->user->hasVerifiedEmail()) {
                return;
            }
            $link = BotLink::query()->where('user_id', $report->user_id)->first();
            if ($link !== null) {
                $this->outbox->enqueue($link, 'news_media_report', (string) $report->id, ['format' => 'html']);
            }
        } catch (Throwable $exception) {
            // Keep completed website results while allowing optional bot delivery to be replayed.
            Log::warning('News and media report delivery unavailable.', ['report_id' => $event->reportId, 'exception_class' => $exception::class]);
            throw $exception;
        }
    }
}
