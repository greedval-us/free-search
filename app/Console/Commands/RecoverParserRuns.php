<?php

namespace App\Console\Commands;

use App\Models\ParserRun;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunRecovery;
use App\Modules\ParserSupport\ParserRunStoreRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecoverParserRuns extends Command
{
    private const RESUME_KEY = 'parser-run:recovery:scan-position';

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
        $paused = false;
        $deadline = hrtime(true) + $config->recoveryMaxPassSeconds() * 1000000000;
        $at = now();
        // Freeze the cycle's upper key across passes so arrivals cannot postpone wrapping forever.
        $latestId = (int) (ParserRun::query()->max('id') ?? 0);
        $position = $dryRun ? [] : (array) Cache::get(self::RESUME_KEY, []);
        $afterId = max(0, (int) ($position['after'] ?? 0));
        $upperId = max(0, min($latestId, (int) ($position['upper'] ?? $latestId)));
        if ($afterId >= $upperId) {
            $afterId = 0;
            $upperId = $latestId;
        }
        $lastId = $afterId;
        ParserRun::query()
            ->select(['id', 'run_id', 'module', 'user_id'])
            ->where('id', '>', $afterId)
            ->where('id', '<=', $upperId)
            ->where('status', ParserRunStatus::Running->value)
            ->where('last_activity_at', '<=', $at->copy()->subSeconds($config->recoveryStaleAfterSeconds()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $at))
            ->chunkById($config->recoveryBatchSize(), function ($runs) use ($stores, $recovery, $dryRun, $deadline, $upperId, &$matched, &$failed, &$paused, &$lastId): ?bool {
                foreach ($runs as $run) {
                    if (hrtime(true) >= $deadline) {
                        $paused = true;

                        return false;
                    }
                    $matched++;
                    $lastId = (int) $run->id;
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
                    } finally {
                        // Busy, invalid and failed candidates must not starve later IDs on the next tick.
                        Cache::put(self::RESUME_KEY, ['after' => $lastId, 'upper' => $upperId], 86400);
                    }
                }

                return null;
            });

        if (! $dryRun && ! $paused) {
            Cache::forget(self::RESUME_KEY);
        }
        $this->info(sprintf('%s recovery %s. %d candidates, %d errors.', $dryRun ? 'Dry run' : 'Parser',
            $paused ? "time budget reached after ID {$lastId}" : 'scan complete', $matched, $failed,
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
