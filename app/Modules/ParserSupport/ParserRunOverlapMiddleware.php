<?php

namespace App\Modules\ParserSupport;

use Illuminate\Queue\Middleware\WithoutOverlapping;

class ParserRunOverlapMiddleware extends WithoutOverlapping
{
    public function handle($job, $next)
    {
        $acquired = false;
        parent::handle($job, function ($command) use ($next, &$acquired): void {
            $acquired = true;
            $next($command);
        });

        if (! $acquired) {
            app(ParserRunTelemetry::class)->record('Parser run queue wait measured.', $job->module, $job->runId,
                $job->checkpointVersion, 'module_busy', [
                    'wait_seconds' => $job->queuedAt === null ? null : max(0, now()->timestamp - $job->queuedAt),
                    'release_seconds' => $this->releaseAfter,
                ],
            );
        }
    }
}
