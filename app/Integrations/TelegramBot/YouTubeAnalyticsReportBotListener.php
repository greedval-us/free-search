<?php

namespace App\Integrations\TelegramBot;

use App\Modules\YouTube\Analytics\Reports\Events\AnalyticsReportCompleted;

final readonly class YouTubeAnalyticsReportBotListener
{
    public function __construct(private CompletedReportBotDispatcher $dispatcher, private YouTubeAnalyticsReportArtifactProvider $provider) {}

    public function handle(AnalyticsReportCompleted $event): void
    {
        $this->dispatcher->dispatch($this->provider, $event->reportId);
    }
}
