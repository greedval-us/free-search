<?php

namespace App\Modules\ParserSupport\Contracts;

interface ParserRunBackgroundProcessorInterface
{
    public const CONTAINER_TAG = 'parser-run.background-processors';

    public function moduleKey(): string;

    public function advanceRun(int $userId, string $runId, ?int $checkpointVersion = null): bool;

    public function failRun(int $userId, string $runId, string $message, ?int $checkpointVersion = null, bool $waitForLock = true): void;
}
