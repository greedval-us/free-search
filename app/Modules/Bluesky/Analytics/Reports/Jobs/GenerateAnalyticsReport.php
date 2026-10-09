<?php

namespace App\Modules\Bluesky\Analytics\Reports\Jobs;

use App\Modules\Bluesky\Analytics\Reports\AnalyticsReportGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateAnalyticsReport implements ShouldQueue
{
    use Queueable;

    // Persisted attempts and leases allow scheduler recovery after worker crashes.
    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $reportId, public readonly string $token)
    {
        $this->timeout = (int) config('bluesky_analytics_reports.queue.timeout');
        $this->onConnection(config('bluesky_analytics_reports.queue.connection'));
        $this->onQueue(config('bluesky_analytics_reports.queue.name'));
    }

    public function handle(AnalyticsReportGenerator $generator): void
    {
        $generator->generate($this->reportId, $this->token);
    }
}
