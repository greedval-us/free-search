<?php

namespace App\Modules\Bluesky\Analytics\Reports\Console;

use App\Modules\Bluesky\Analytics\Reports\AnalyticsReportScheduler;
use Illuminate\Console\Command;

final class MaintainAnalyticsReports extends Command
{
    protected $signature = 'bluesky:analytics-reports-maintain';

    protected $description = 'Create due Bluesky analytics reports and recover pending or interrupted generation';

    public function handle(AnalyticsReportScheduler $scheduler): int
    {
        $this->info('Analytics reports dispatched: '.$scheduler->maintain());

        return self::SUCCESS;
    }
}
