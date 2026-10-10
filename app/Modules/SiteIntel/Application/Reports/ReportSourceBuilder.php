<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Models\SiteIntelScheduledReport;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelAnalyticsServiceInterface;
use App\Modules\SiteIntel\Support\SiteIntelTargetGuard;

final readonly class ReportSourceBuilder
{
    public function __construct(
        private SiteIntelAnalyticsServiceInterface $analytics,
        private SeoAuditServiceInterface $seo,
        private SiteIntelTargetGuard $guard,
    ) {}

    public function build(SiteIntelScheduledReport $report): array
    {
        $url = PublicSiteTarget::normalize($report->target_url);
        if ($url === null) {
            throw new ReportException('invalid_target');
        }
        // A previously valid hostname can resolve to a private address by execution time.
        // Existing HTTP clients additionally validate redirects and pin each resolved IP.
        $target = $this->guard->resolveSafeTarget($url);
        $data = match ($report->report_type) {
            'analytics' => $this->analytics->analyze($url, $target->host)->toArray(),
            'seo-audit' => $this->seo->audit($url, $report->crawl_limit,
                $report->platform_type === 'auto' ? null : $report->platform_type)->toArray(),
            default => throw new ReportException('invalid_type'),
        };
        $data['reportSchedule'] = [
            'type' => $report->report_type,
            'targetUrl' => $url,
            'scheduledFor' => $report->scheduled_for->toIso8601String(),
            'checkedAt' => $data['checkedAt'] ?? now()->toIso8601String(),
            'timezone' => $report->schedule->timezone,
            'frequency' => $report->schedule->interval,
            'metricsBasis' => 'snapshot_at_check_time',
        ];

        return $data;
    }
}
