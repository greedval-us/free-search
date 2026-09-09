<?php

namespace App\Console\Commands;

use App\Models\ParserRun;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunFileStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CleanupParserRunFiles extends Command
{
    protected $signature = 'app:cleanup-parser-runs {--dry-run : Show what would be deleted without deleting it}';

    protected $description = 'Delete expired parser run JSON files from private storage and remove their metadata.';

    public function handle(ParserRunConfig $config, ParserRunFileStorage $files): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $batchSize = $config->cleanupBatchSize();
        $matchedRuns = 0;
        $deletedFiles = 0;
        $deletedRows = 0;
        $failedFiles = 0;

        if (! ParserRun::query()->expired()->exists()) {
            $this->info('No expired parser runs found.');
            $this->logSummary($config, $isDryRun, 0, 0, 0);

            return self::SUCCESS;
        }

        ParserRun::query()
            ->expired()
            ->orderBy('id')
            ->chunkById($batchSize, function ($runs) use (
                $isDryRun,
                $files,
                &$matchedRuns,
                &$deletedFiles,
                &$deletedRows,
                &$failedFiles
            ): void {
                foreach ($runs as $run) {
                    $matchedRuns++;

                    if ($isDryRun) {
                        $this->line($this->dryRunMessage($run));

                        continue;
                    }

                    try {
                        $path = Storage::disk($run->file_disk)->path($run->file_path);
                        $files->withExclusiveLock($path, function () use ($run, &$deletedFiles, &$deletedRows): void {
                            $current = $run->fresh();
                            if ($current === null || ! $current->expires_at?->isPast()) {
                                return;
                            }

                            $deletedFiles += (int) $this->deleteStoredFile($current);
                            $current->delete();
                            $deletedRows++;
                        });
                    } catch (Throwable $exception) {
                        $failedFiles++;
                        Log::error('Parser run file cleanup failed; metadata retained for retry.', [
                            'run_id' => $run->run_id,
                            'exception' => $exception::class,
                        ]);

                        continue;
                    }

                }
            });

        if ($isDryRun) {
            $this->info(sprintf('Dry run complete. %d expired parser runs matched.', $matchedRuns));
            $this->logSummary($config, true, $matchedRuns, 0, 0);

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Cleanup complete. Deleted %d metadata rows and %d files.',
            $deletedRows,
            $deletedFiles
        ));
        $this->logSummary($config, false, $matchedRuns, $deletedRows, $deletedFiles);

        if ($failedFiles > 0) {
            $this->error(sprintf('Unable to delete %d files. Their metadata was retained for retry.', $failedFiles));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function deleteStoredFile(ParserRun $run): bool
    {
        $disk = Storage::disk($run->file_disk);

        if (! $disk->exists($run->file_path)) {
            return false;
        }

        if (! $disk->delete($run->file_path)) {
            throw new RuntimeException('Unable to delete expired parser file.');
        }

        return true;
    }

    private function dryRunMessage(ParserRun $run): string
    {
        return sprintf('[dry-run] %s %s %s', $run->run_id, $run->file_disk, $run->file_path);
    }

    private function logSummary(
        ParserRunConfig $config,
        bool $isDryRun,
        int $matchedRuns,
        int $deletedRows,
        int $deletedFiles,
    ): void {
        Log::info('Parser run cleanup completed.', [
            'dry_run' => $isDryRun,
            'matched_runs' => $matchedRuns,
            'deleted_rows' => $deletedRows,
            'deleted_files' => $deletedFiles,
            'batch_size' => $config->cleanupBatchSize(),
            'retention_days' => $config->retentionDays(),
        ]);
    }
}
