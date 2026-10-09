<?php

namespace App\Integrations\TelegramBot;

use App\Modules\Bluesky\Analytics\Reports\Events\AnalyticsReportCompleted;

final readonly class BlueskyAnalyticsReportBotListener
{
    public function __construct(private CompletedReportBotDispatcher $dispatcher, private BlueskyAnalyticsReportArtifactProvider $provider) {}

    public function handle(AnalyticsReportCompleted $event): void
    {
        $this->dispatcher->dispatch($this->provider, $event->reportId);
    }
}
