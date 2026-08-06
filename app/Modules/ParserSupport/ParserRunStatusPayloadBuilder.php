<?php

namespace App\Modules\ParserSupport;

use App\Models\ParserRun;

class ParserRunStatusPayloadBuilder
{
    /**
     * @param array<string, mixed> $run
     * @param array<string, string> $statsMap outputKey => runStatsKey
     * @return array<string, mixed>
     */
    public function build(array $run, array $statsMap, string $excelRoute, string $jsonRoute): array
    {
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];
        $status = ParserRun::normalizeStatus($run['status'] ?? null);
        $runId = (string) ($run['runId'] ?? '');
        $hasResult = is_array($run['result'] ?? null);
        $isDownloadable = ParserRun::isDownloadableStatus($status) && $hasResult && $runId !== '';

        $payload = [
            'ok' => true,
            'runId' => $runId,
            'status' => $status,
            'stage' => (string) ($run['stage'] ?? 'idle'),
            'progress' => ParserRun::normalizeProgress($run['progress'] ?? null),
            'error' => $run['error'] ?? null,
            'downloadUrl' => $isDownloadable
                ? route($excelRoute, ['runId' => $runId])
                : null,
            'downloadJsonUrl' => $isDownloadable
                ? route($jsonRoute, ['runId' => $runId])
                : null,
        ];

        foreach ($statsMap as $outputKey => $statsKey) {
            $payload[$outputKey] = (int) ($stats[$statsKey] ?? 0);
        }

        return $payload;
    }
}
