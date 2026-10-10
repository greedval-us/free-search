<?php

namespace App\Integrations\TelegramBot;

use App\Modules\Mastodon\Analytics\Reports\Events\AnalyticsReportCompleted;

final readonly class MastodonAnalyticsReportBotListener
{
    public function __construct(private CompletedReportBotDispatcher $dispatcher, private MastodonAnalyticsReportArtifactProvider $provider) {}

    public function handle(AnalyticsReportCompleted $event): void
    {
        $this->dispatcher->dispatch($this->provider, $event->reportId);
    }
}
