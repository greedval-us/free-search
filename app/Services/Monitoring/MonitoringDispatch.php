<?php

namespace App\Services\Monitoring;

use App\Jobs\Monitoring\BuildMonitoringReport;
use App\Jobs\Monitoring\CollectMonitoringSource;
use App\Jobs\Monitoring\ExportMonitoringReport;
use App\Jobs\Monitoring\ValidateMonitoringSource;
use App\Models\MonitoringCollection;
use App\Models\MonitoringReport;
use App\Models\MonitoringSource;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

final class MonitoringDispatch
{
    public function source(MonitoringSource $source): void
    {
        $this->send(new ValidateMonitoringSource($source->id, $source->project->user_id, $source->generation, $source->project->generation), $source);
    }

    public function collection(MonitoringCollection $collection): void
    {
        $this->send(new CollectMonitoringSource($collection->id, $collection->user_id), $collection);
    }

    public function report(MonitoringReport $report): void
    {
        $job = in_array($report->status, MonitoringReport::FINAL_STATUSES, true)
            ? new ExportMonitoringReport($report->id, $report->user_id)
            : new BuildMonitoringReport($report->id, $report->user_id);
        $this->send($job, $report);
    }

    private function send(object $job, object $model): void
    {
        try {
            $job->onConnection(config('monitoring.connection'))->onQueue(config('monitoring.queue'));
            Bus::dispatch($job);
            $model->forceFill(['dispatched_at' => now()])->save();
        } catch (Throwable) {
            // DB work remains pending. Scheduler will recover dispatch without browser traffic.
            Log::warning('monitoring.dispatch_unavailable', ['type' => $model::class, 'id' => $model->id]);
        }
    }
}
