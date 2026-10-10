<?php

namespace App\Modules\Mastodon\Analytics\Reports\Console;

use App\Modules\Mastodon\Analytics\Reports\AnalyticsReportScheduler;
use Illuminate\Console\Command;

final class MaintainAnalyticsReports extends Command
{
    protected $signature = 'mastodon:analytics-reports-maintain';

    protected $description = 'Create due Mastodon analytics reports and recover pending or interrupted generation';

    public function handle(AnalyticsReportScheduler $scheduler): int
    {
        $this->info('Analytics reports dispatched: '.$scheduler->maintain());

        return self::SUCCESS;
    }
}
