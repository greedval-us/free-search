<?php

namespace App\Integrations\TelegramBot;

use App\Models\MastodonAnalyticsReport;
use Illuminate\Database\Eloquent\Model;

final readonly class MastodonAnalyticsReportArtifactProvider extends FeatureReportArtifactProvider
{
    public function key(): string
    {
        return 'mastodon_analytics_report';
    }

    protected function model(): string
    {
        return MastodonAnalyticsReport::class;
    }

    protected function feature(): string
    {
        return 'mastodon.analytics';
    }

    protected function filenamePrefix(): string
    {
        return 'mastodon-analytics';
    }

    protected function view(Model $report): string
    {
        return 'reports.mastodon.analytics';
    }

    protected function listingColumns(): array
    {
        return ['id', 'schedule_id', 'account_input', 'date_from', 'date_to'];
    }

    protected function label(Model $report): string
    {
        return $report->account_input.' / '.$this->periodLabel($report);
    }
}
