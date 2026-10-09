<?php

namespace App\Integrations\TelegramBot;

use App\Modules\NewsMediaIntel\Application\Reports\Events\NewsMediaReportCompleted;

final readonly class NewsMediaReportBotListener
{
    public function __construct(private CompletedReportBotDispatcher $dispatcher, private NewsMediaReportArtifactProvider $provider) {}

    public function handle(NewsMediaReportCompleted $event): void
    {
        $this->dispatcher->dispatch($this->provider, $event->reportId);
    }
}
