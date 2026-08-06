<?php

namespace App\Modules\ParserSupport;

use App\Models\ParserRun;

final class ParserRunHistoryItemBuilder
{
    /**
     * @param array<string, mixed>|null $run
     * @param null|callable(array<string, mixed>|null, ParserRun): array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public function build(
        ParserRun $metadata,
        ?array $run,
        string $excelRoute,
        string $jsonRoute,
        ?callable $extra = null,
    ): array {
        $status = ParserRun::normalizeStatus($metadata->status);
        $downloadable = ParserRun::isDownloadableStatus($status)
            && is_array($run)
            && is_array($run['result'] ?? null);

        return array_merge([
            'runId' => $metadata->run_id,
            'status' => $status,
            'stage' => $this->stringOrFallback($run['stage'] ?? null, $metadata->stage),
            'progress' => ParserRun::normalizeProgress($run['progress'] ?? $metadata->progress),
            'error' => $this->stringOrFallback($run['error'] ?? null, $metadata->error),
            'createdAt' => $this->timestampOrFallback($run['createdAt'] ?? null, $metadata->started_at?->toIso8601String()),
            'updatedAt' => $this->timestampOrFallback($run['updatedAt'] ?? null, $metadata->last_activity_at?->toIso8601String()),
            'finishedAt' => $metadata->finished_at?->toIso8601String(),
            'expiresAt' => $metadata->expires_at?->toIso8601String(),
            'downloadable' => $downloadable,
            'downloadUrl' => $downloadable ? route($excelRoute, ['runId' => $metadata->run_id]) : null,
            'downloadJsonUrl' => $downloadable ? route($jsonRoute, ['runId' => $metadata->run_id]) : null,
        ], $extra ? $extra($run, $metadata) : []);
    }

    private function stringOrFallback(mixed $value, ?string $fallback): ?string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    private function timestampOrFallback(mixed $value, ?string $fallback): ?string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }
}
