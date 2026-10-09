<?php

namespace App\Modules\Telegram\Analytics\Reports\Console;

use App\Modules\Telegram\Analytics\Reports\AnalyticsReportScheduler;
use Illuminate\Console\Command;

final class MaintainAnalyticsReports extends Command
{
    protected $signature = 'telegram:analytics-reports-maintain';

    protected $description = 'Create due Telegram analytics reports and recover pending or interrupted generation';

    public function handle(AnalyticsReportScheduler $scheduler): int
    {
        $this->info('Analytics reports dispatched: '.$scheduler->maintain());

        return self::SUCCESS;
    }
}
