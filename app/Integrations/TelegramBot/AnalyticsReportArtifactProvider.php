<?php

namespace App\Integrations\TelegramBot;

use App\Models\TelegramAnalyticsReport;
use Illuminate\Database\Eloquent\Model;

final readonly class AnalyticsReportArtifactProvider extends FeatureReportArtifactProvider
{
    public function key(): string
    {
        return 'analytics_report';
    }

    protected function model(): string
    {
        return TelegramAnalyticsReport::class;
    }

    protected function feature(): string
    {
        return 'telegram.analytics';
    }

    protected function filenamePrefix(): string
    {
        return 'telegram-analytics';
    }

    protected function view(Model $report): string
    {
        return 'reports.telegram.analytics';
    }

    protected function listingColumns(): array
    {
        return ['id', 'schedule_id', 'chat_username', 'date_from', 'date_to'];
    }

    protected function label(Model $report): string
    {
        return '@'.$report->chat_username.' / '.$this->periodLabel($report);
    }

    protected function viewData(Model $report): array
    {
        $data = $report->data;
        $previousReport = $data['previousReport'] ?? null;
        unset($data['previousReport']);

        return [...parent::viewData($report), 'report' => $data, 'previousReport' => $previousReport];
    }
}
