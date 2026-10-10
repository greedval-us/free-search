<?php

namespace App\Modules\Mastodon\Analytics\Reports\Jobs;

use App\Modules\Mastodon\Analytics\Reports\AnalyticsReportGenerator;
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
        $this->timeout = (int) config('mastodon_analytics_reports.queue.timeout');
        $this->onConnection(config('mastodon_analytics_reports.queue.connection'));
        $this->onQueue(config('mastodon_analytics_reports.queue.name'));
    }

    public function handle(AnalyticsReportGenerator $generator): void
    {
        $generator->generate($this->reportId, $this->token);
    }
}
