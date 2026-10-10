<?php

namespace App\Integrations\TelegramBot;

use App\Models\YouTubeAnalyticsReport;
use Illuminate\Database\Eloquent\Model;

final readonly class YouTubeAnalyticsReportArtifactProvider extends FeatureReportArtifactProvider
{
    public function key(): string
    {
        return 'youtube_analytics_report';
    }

    protected function model(): string
    {
        return YouTubeAnalyticsReport::class;
    }

    protected function feature(): string
    {
        return 'youtube.analytics';
    }

    protected function filenamePrefix(): string
    {
        return 'youtube-analytics';
    }

    protected function view(Model $report): string
    {
        return 'reports.youtube.analytics';
    }

    protected function listingColumns(): array
    {
        return ['id', 'schedule_id', 'channel_input', 'date_from', 'date_to'];
    }

    protected function label(Model $report): string
    {
        return $report->channel_input.' / '.$this->periodLabel($report);
    }
}
