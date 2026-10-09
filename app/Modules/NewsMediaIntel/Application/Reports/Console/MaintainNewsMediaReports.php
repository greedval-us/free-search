<?php

namespace App\Modules\NewsMediaIntel\Application\Reports\Console;

use App\Modules\NewsMediaIntel\Application\Reports\ReportScheduler;
use Illuminate\Console\Command;

final class MaintainNewsMediaReports extends Command
{
    protected $signature = 'news-media:reports-maintain';

    protected $description = 'Create due News media reports and recover pending or interrupted generation';

    public function handle(ReportScheduler $scheduler): int
    {
        $this->info('News media reports dispatched: '.$scheduler->maintain());

        return self::SUCCESS;
    }
}
