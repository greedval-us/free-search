<?php

namespace App\Modules\SiteIntel\Application\Reports\Jobs;

use App\Modules\SiteIntel\Application\Reports\ReportGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateSiteIntelReport implements ShouldQueue
{
    use Queueable;

    // Persisted attempts and leases allow scheduler recovery after worker crashes.
    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $reportId, public readonly string $token)
    {
        $this->timeout = (int) config('site_intel_reports.queue.timeout');
        $this->onConnection(config('site_intel_reports.queue.connection'));
        $this->onQueue(config('site_intel_reports.queue.name'));
    }

    public function handle(ReportGenerator $generator): void
    {
        $generator->generate($this->reportId, $this->token);
    }
}
