<?php

namespace App\Console\Commands;

use App\Models\ParserRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupParserRunFiles extends Command
{
    protected $signature = 'app:cleanup-parser-runs {--dry-run : Show what would be deleted without deleting it}';

    protected $description = 'Delete expired parser run JSON files from private storage and remove their metadata.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $batchSize = $this->batchSize();
        $matchedRuns = 0;
        $deletedFiles = 0;
        $deletedRows = 0;

        if (! ParserRun::query()->expired()->exists()) {
            $this->info('No expired parser runs found.');
            $this->logSummary($isDryRun, 0, 0, 0);

            return self::SUCCESS;
        }

        ParserRun::query()
            ->expired()
            ->orderBy('id')
            ->chunkById($batchSize, function ($runs) use (
                $isDryRun,
                &$matchedRuns,
                &$deletedFiles,
                &$deletedRows
            ): void {
                foreach ($runs as $run) {
                    $matchedRuns++;

                    if ($isDryRun) {
                        $this->line($this->dryRunMessage($run));

                        continue;
                    }

                    $deletedFiles += (int) $this->deleteStoredFile($run);

                    $run->delete();
                    $deletedRows++;
                }
            });

        if ($isDryRun) {
            $this->info(sprintf('Dry run complete. %d expired parser runs matched.', $matchedRuns));
            $this->logSummary(true, $matchedRuns, 0, 0);

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Cleanup complete. Deleted %d metadata rows and %d files.',
            $deletedRows,
            $deletedFiles
        ));
        $this->logSummary(false, $matchedRuns, $deletedRows, $deletedFiles);

        return self::SUCCESS;
    }

    private function batchSize(): int
    {
        return max(1, (int) config('osint.parser_runs.cleanup_batch_size', 500));
    }

    private function deleteStoredFile(ParserRun $run): bool
    {
        $disk = Storage::disk($run->file_disk);

        if (! $disk->exists($run->file_path)) {
            return false;
        }

        return $disk->delete($run->file_path);
    }

    private function dryRunMessage(ParserRun $run): string
    {
        return sprintf('[dry-run] %s %s %s', $run->run_id, $run->file_disk, $run->file_path);
    }

    private function logSummary(bool $isDryRun, int $matchedRuns, int $deletedRows, int $deletedFiles): void
    {
        Log::info('Parser run cleanup completed.', [
            'dry_run' => $isDryRun,
            'matched_runs' => $matchedRuns,
            'deleted_rows' => $deletedRows,
            'deleted_files' => $deletedFiles,
            'batch_size' => $this->batchSize(),
            'retention_days' => (int) config('osint.parser_runs.retention_days', 30),
        ]);
    }
}
