<?php

namespace App\Console\Commands;

use App\Models\ParserRun;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunRecovery;
use App\Modules\ParserSupport\ParserRunStoreRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecoverParserRuns extends Command
{
    protected $signature = 'app:recover-parser-runs {--dry-run : List stale candidates without locks, writes or jobs}';

    protected $description = 'Requeue stale running parser checkpoints without resetting their retry budget.';

    public function handle(ParserRunConfig $config, ParserRunStoreRegistry $stores, ParserRunRecovery $recovery): int
    {
        if (! $config->queueEnabled()) {
            $this->info('Parser queue execution is disabled.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $matched = 0;
        $failed = 0;
        $at = now();
        // Freeze the upper key so concurrently created runs cannot extend this scan forever.
        $upperId = ParserRun::query()->max('id');
        ParserRun::query()
            ->select(['id', 'run_id', 'module', 'user_id'])
            ->where('id', '<=', $upperId ?? 0)
            ->where('status', ParserRunStatus::Running->value)
            ->where('last_activity_at', '<=', $at->copy()->subSeconds($config->recoveryStaleAfterSeconds()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $at))
            ->chunkById($config->recoveryBatchSize(), function ($runs) use ($stores, $recovery, $dryRun, &$matched, &$failed): void {
                foreach ($runs as $run) {
                    $matched++;
                    if ($dryRun) {
                        $this->line("[dry-run] {$run->module} {$run->run_id}");

                        continue;
                    }

                    try {
                        $outcome = $recovery->recover($stores->forModule($run->module), $run->user_id, $run->run_id);
                        $this->line("{$run->module} {$run->run_id}: {$outcome}");
                    } catch (Throwable $exception) {
                        $failed++;
                        Log::warning('Parser run recovery candidate failed.', [
                            'run_id' => $run->run_id, 'module' => $run->module, 'exception' => $exception::class,
                        ]);
                    }
                }
            });

        $this->info(sprintf('%s recovery scan complete. %d candidates, %d errors.', $dryRun ? 'Dry run' : 'Parser', $matched, $failed));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
