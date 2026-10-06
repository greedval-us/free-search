<?php

namespace App\Console\Commands;

use App\Models\MonitoringCollection;
use App\Models\MonitoringMaterial;
use App\Models\MonitoringReport;
use App\Services\Monitoring\MonitoringScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class MaintainMonitoring extends Command
{
    protected $signature = 'monitoring:maintain {--prune : Remove expired monitoring snapshots and private files}';

    protected $description = 'Schedule monitoring and recover pending work without a browser';

    public function handle(MonitoringScheduler $scheduler): int
    {
        if (! $this->option('prune')) {
            $scheduler->tick();

            return self::SUCCESS;
        }
        $removed = 0;
        foreach (MonitoringReport::query()->where('expires_at', '<=', now())->lazyById(100) as $report) {
            Storage::disk('private')->deleteDirectory('monitoring/'.$report->user_id.'/'.$report->project_id.'/'.$report->id);
            $report->delete();
            $removed++;
        }
        // Immutable item snapshots do not depend on mutable source material retention.
        $days = max(array_column(config('monitoring.plans'), 'retention_days'));
        MonitoringMaterial::query()->where('collected_at', '<', now()->subDays($days))->delete();
        MonitoringCollection::query()->whereNotIn('status', ['queued', 'running'])->where('end_at', '<', now()->subDays($days))->delete();
        $this->info('Expired monitoring reports removed: '.$removed);

        return self::SUCCESS;
    }
}
