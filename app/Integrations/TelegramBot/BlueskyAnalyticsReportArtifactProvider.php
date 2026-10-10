<?php

namespace App\Integrations\TelegramBot;

use App\Models\BlueskyAnalyticsReport;
use Illuminate\Database\Eloquent\Model;

final readonly class BlueskyAnalyticsReportArtifactProvider extends FeatureReportArtifactProvider
{
    public function key(): string
    {
        return 'bluesky_analytics_report';
    }

    protected function model(): string
    {
        return BlueskyAnalyticsReport::class;
    }

    protected function feature(): string
    {
        return 'bluesky.analytics';
    }

    protected function filenamePrefix(): string
    {
        return 'bluesky-analytics';
    }

    protected function view(Model $report): string
    {
        return 'reports.bluesky.analytics';
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
