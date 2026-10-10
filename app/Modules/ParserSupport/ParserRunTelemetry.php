<?php

namespace App\Modules\ParserSupport;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ParserRunTelemetry
{
    /** @param array<string, int|string|null> $measurements */
    public function record(string $event, string $module, string $runId, ?int $version, string $reason, array $measurements): void
    {
        if (! Cache::add("parser-run:telemetry:{$module}:{$runId}:{$reason}", true, 60)) {
            return;
        }

        Log::info($event, [
            'module' => $module, 'run_id' => $runId, 'checkpoint_version' => $version, 'reason' => $reason,
            ...$measurements,
        ]);
    }
}
