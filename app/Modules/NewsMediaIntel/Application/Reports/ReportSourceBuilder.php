<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Models\NewsMediaScheduledReport;
use App\Modules\NewsMediaIntel\Application\Services\Marketing\NewsMarketingAnalyticsService;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMarketingLookupDTO;

final readonly class ReportSourceBuilder
{
    public function __construct(private NewsMarketingAnalyticsService $analytics, private ReportParameters $parameters) {}

    public function build(NewsMediaScheduledReport $report): array
    {
        $parameters = $this->parameters->normalize([
            'queries' => [$report->query], 'brand' => $report->brand, 'competitors' => $report->competitors,
            'domain' => $report->domain, 'search_options' => $report->search_options,
        ]);
        $data = $this->analytics->analyze(new NewsMarketingLookupDTO(
            query: $parameters['queries'][0], options: $this->parameters->options($parameters['search_options']),
            brand: $parameters['brand'], competitors: $parameters['competitors'], domain: $parameters['domain'],
        ));
        $data['reportSchedule'] = [
            'scheduledFor' => $report->scheduled_for->toIso8601String(),
            'checkedAt' => $data['checkedAt'] ?? now()->toIso8601String(),
            'timezone' => $report->schedule->timezone, 'frequency' => $report->schedule->interval,
            'metricsBasis' => 'sample_at_check_time',
        ];

        return $data;
    }
}
