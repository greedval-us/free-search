<?php

namespace App\Modules\NewsMediaIntel\Application\Reports\Jobs;

use App\Modules\NewsMediaIntel\Application\Reports\ReportGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateNewsMediaReport implements ShouldQueue
{
    use Queueable;

    // Persisted attempts and leases allow scheduler recovery after worker crashes.
    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $reportId, public readonly string $token)
    {
        $this->timeout = (int) config('news_media_reports.queue.timeout');
        $this->onConnection(config('news_media_reports.queue.connection'));
        $this->onQueue(config('news_media_reports.queue.name'));
    }

    public function handle(ReportGenerator $generator): void
    {
        $generator->generate($this->reportId, $this->token);
    }
}
