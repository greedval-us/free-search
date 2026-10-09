<?php

namespace App\Integrations\TelegramBot;

use App\Models\SiteIntelScheduledReport;
use App\Models\User;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Services\Access\SiteIntelReportAccess;
use App\Support\Reports\SavedReportRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final readonly class SiteIntelReportArtifactProvider extends SavedReportArtifactProvider
{
    public function __construct(BotConfig $config, TemporaryDocuments $files, SavedReportRenderer $renderer, private SiteIntelReportAccess $access)
    {
        parent::__construct($config, $files, $renderer);
    }

    public function key(): string
    {
        return 'site_intel_report';
    }

    protected function model(): string
    {
        return SiteIntelScheduledReport::class;
    }

    protected function filenamePrefix(): string
    {
        return 'site-intel-report';
    }

    protected function view(Model $report): string
    {
        return $report->report_type === 'seo-audit' ? 'reports.site-intel.seo-audit' : 'reports.site-intel.analytics';
    }

    protected function moduleAllows(User $user): bool
    {
        return $this->access->availableTypes($user) !== [];
    }

    protected function constrainAccess(Builder $query, User $user): void
    {
        $query->whereIn('report_type', $this->access->availableTypes($user));
    }

    protected function listingColumns(): array
    {
        return ['id', 'schedule_id', 'target_url', 'report_type', 'completed_at'];
    }

    protected function label(Model $report): string
    {
        return $report->target_url.' / '.__('site_intel_reports.report.'.$report->report_type).' / '
            .$report->completed_at?->setTimezone($this->timezone($report))->format('d.m.Y H:i');
    }

    protected function viewData(Model $report): array
    {
        return ['report' => $report->data, 'generatedAt' => $report->completed_at?->setTimezone(
            $report->data['reportSchedule']['timezone'] ?? $this->timezone($report)
        )->format('d.m.Y H:i')];
    }
}
