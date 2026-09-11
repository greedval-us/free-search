<?php

namespace App\Modules\Telegram\Tracking\Console;

use App\Modules\Telegram\Tracking\TrackingScheduler;
use Illuminate\Console\Command;

final class MaintainTracking extends Command
{
    protected $signature = 'telegram:tracking-maintain';

    protected $description = 'Pause ineligible tracking, expire tasks, prune retained data and dispatch due sources';

    public function handle(TrackingScheduler $scheduler): int
    {
        $scheduler->maintain();
        $this->info('Tracking sources dispatched: '.$scheduler->dispatch());

        return self::SUCCESS;
    }
}
