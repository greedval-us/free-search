<?php

namespace App\Integrations\TelegramBot;

use App\Models\YouTubeAnalyticsReport;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Modules\YouTube\Analytics\Reports\Events\AnalyticsReportCompleted;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class YouTubeAnalyticsReportBotListener
{
    public function __construct(private BotConfig $config, private DeliveryOutbox $outbox) {}

    public function handle(AnalyticsReportCompleted $event): void
    {
        if (! $this->config->active()) {
            return;
        }

        try {
            $report = YouTubeAnalyticsReport::query()->with('schedule')
                ->where('status', YouTubeAnalyticsReport::COMPLETED)
                ->find($event->reportId, ['id', 'user_id', 'schedule_id', 'is_manual']);
            if ($report === null || $report->schedule === null || $report->schedule->trashed()
                || (! $report->schedule->enabled && ! $report->is_manual) || ! $report->schedule->send_to_bot
                || $report->schedule->user_id !== $report->user_id) {
                return;
            }
            $link = BotLink::query()->where('user_id', $report->user_id)->first();
            if ($link !== null) {
                $this->outbox->enqueue($link, 'youtube_analytics_report', (string) $report->id, ['format' => 'html']);
            }
        } catch (Throwable $exception) {
            // Report generation remains available when this optional delivery channel fails.
            Log::warning('YouTube analytics report delivery unavailable.', ['report_id' => $event->reportId, 'exception_class' => $exception::class]);
            throw $exception;
        }
    }
}
