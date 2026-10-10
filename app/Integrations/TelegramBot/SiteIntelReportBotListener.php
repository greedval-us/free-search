<?php

namespace App\Integrations\TelegramBot;

use App\Modules\SiteIntel\Application\Reports\Events\SiteIntelReportCompleted;

final readonly class SiteIntelReportBotListener
{
    public function __construct(private CompletedReportBotDispatcher $dispatcher, private SiteIntelReportArtifactProvider $provider) {}

    public function handle(SiteIntelReportCompleted $event): void
    {
        $this->dispatcher->dispatch($this->provider, $event->reportId);
    }
}
