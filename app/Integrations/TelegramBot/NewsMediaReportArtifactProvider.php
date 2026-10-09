<?php

namespace App\Integrations\TelegramBot;

use App\Models\NewsMediaScheduledReport;
use Illuminate\Database\Eloquent\Model;

final readonly class NewsMediaReportArtifactProvider extends SavedReportArtifactProvider
{
    public function key(): string
    {
        return 'news_media_report';
    }

    protected function model(): string
    {
        return NewsMediaScheduledReport::class;
    }

    protected function filenamePrefix(): string
    {
        return 'news-media-report';
    }

    protected function view(Model $report): string
    {
        return 'reports.news-media-intel.analytics';
    }

    protected function listingColumns(): array
    {
        return ['id', 'schedule_id', 'query', 'completed_at'];
    }

    protected function label(Model $report): string
    {
        return $report->query.' / '.$report->completed_at?->setTimezone($this->timezone($report))->format('d.m.Y H:i');
    }
}
