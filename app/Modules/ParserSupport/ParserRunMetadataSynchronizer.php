<?php

namespace App\Modules\ParserSupport;

use App\Models\ParserRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

final class ParserRunMetadataSynchronizer
{
    /**
     * @param array<string, mixed> $run
     */
    public function sync(string $module, int $userId, string $disk, string $path, array $run): void
    {
        $status = ParserRun::normalizeStatus($run['status'] ?? null);
        $startedAt = $this->timestamp($run['createdAt'] ?? null);
        $lastActivityAt = $this->timestamp($run['updatedAt'] ?? null) ?? $startedAt ?? now();

        ParserRun::query()->updateOrCreate(
            ['run_id' => (string) ($run['runId'] ?? '')],
            [
                'user_id' => $userId,
                'module' => $module,
                'status' => $status,
                'stage' => $this->stringOrNull($run['stage'] ?? null),
                'progress' => ParserRun::normalizeProgress($run['progress'] ?? null),
                'error' => $this->stringOrNull($run['error'] ?? null),
                'file_disk' => $disk,
                'file_path' => $path,
                'file_size_bytes' => $this->fileSize($disk, $path),
                'started_at' => $startedAt,
                'last_activity_at' => $lastActivityAt,
                'finished_at' => $this->finishedAt($status, $lastActivityAt),
                'expires_at' => $lastActivityAt->copy()->addDays($this->retentionDays()),
            ]
        );
    }

    private function retentionDays(): int
    {
        return max(1, (int) config('osint.parser_runs.retention_days', 30));
    }

    private function finishedAt(string $status, CarbonImmutable $fallback): ?CarbonImmutable
    {
        return ParserRun::isTerminalStatus($status) ? $fallback : null;
    }

    private function fileSize(string $disk, string $path): ?int
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            return null;
        }

        return $storage->size($path);
    }

    private function timestamp(mixed $value): ?CarbonImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
