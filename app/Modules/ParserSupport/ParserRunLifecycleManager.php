<?php

namespace App\Modules\ParserSupport;

final class ParserRunLifecycleManager
{
    /**
     * @param array<string, mixed> $run
     */
    public function isRunning(array $run): bool
    {
        return ($run['status'] ?? null) === 'running';
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function markFailed(array $run, string $message, string $stage = 'failed'): array
    {
        $run['status'] = 'failed';
        $run['stage'] = $stage;
        $run['progress'] = 100;
        $run['error'] = $message;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function markCompleted(array $run, array $result, string $stage = 'completed'): array
    {
        $run['result'] = $result;
        $run['status'] = 'completed';
        $run['stage'] = $stage;
        $run['progress'] = 100;
        $run['error'] = null;

        return $run;
    }
}
