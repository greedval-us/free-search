<?php

namespace App\Integrations\TelegramBot;

use App\Modules\Telegram\Analytics\Reports\Events\AnalyticsReportCompleted;

final readonly class AnalyticsReportBotListener
{
    public function __construct(private CompletedReportBotDispatcher $dispatcher, private AnalyticsReportArtifactProvider $provider) {}

    public function handle(AnalyticsReportCompleted $event): void
    {
        $this->dispatcher->dispatch($this->provider, $event->reportId);
    }
}
