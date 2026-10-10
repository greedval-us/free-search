<?php

namespace App\Modules\SiteIntel\Application\Reports\Console;

use App\Modules\SiteIntel\Application\Reports\ReportScheduler;
use Illuminate\Console\Command;

final class MaintainSiteIntelReports extends Command
{
    protected $signature = 'site-intel:reports-maintain';

    protected $description = 'Create due SiteIntel reports and recover pending or interrupted generation';

    public function handle(ReportScheduler $scheduler): int
    {
        $this->info('Site reports dispatched: '.$scheduler->maintain());

        return self::SUCCESS;
    }
}
