<?php

namespace App\Modules\ParserSupport;

use App\Modules\ParserSupport\Enums\ParserRunStatus;

final class ParserRunLifecycleManager
{
    /**
     * @param array<string, mixed> $run
     */
    public function isRunning(array $run): bool
    {
        return ($run['status'] ?? null) === ParserRunStatus::Running->value;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function markFailed(array $run, string $message, string $stage = 'failed'): array
    {
        $run['status'] = ParserRunStatus::Failed->value;
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
        $run['status'] = ParserRunStatus::Completed->value;
        $run['stage'] = $stage;
        $run['progress'] = 100;
        $run['error'] = null;

        return $run;
    }
}
