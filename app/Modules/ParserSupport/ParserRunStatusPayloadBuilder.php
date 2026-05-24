<?php

namespace App\Modules\ParserSupport;

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
        $status = (string) ($run['status'] ?? 'running');
        $runId = (string) ($run['runId'] ?? '');
        $hasResult = is_array($run['result'] ?? null);

        $payload = [
            'ok' => true,
            'runId' => $runId,
            'status' => $status,
            'stage' => (string) ($run['stage'] ?? 'idle'),
            'progress' => (int) ($run['progress'] ?? 0),
            'error' => $run['error'] ?? null,
            'downloadUrl' => in_array($status, ['completed', 'stopped'], true) && $hasResult && $runId !== ''
                ? route($excelRoute, ['runId' => $runId])
                : null,
            'downloadJsonUrl' => in_array($status, ['completed', 'stopped'], true) && $hasResult && $runId !== ''
                ? route($jsonRoute, ['runId' => $runId])
                : null,
        ];

        foreach ($statsMap as $outputKey => $statsKey) {
            $payload[$outputKey] = (int) ($stats[$statsKey] ?? 0);
        }

        return $payload;
    }
}

