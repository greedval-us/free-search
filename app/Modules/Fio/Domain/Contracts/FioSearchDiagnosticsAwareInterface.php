<?php

namespace App\Modules\Fio\Domain\Contracts;

interface FioSearchDiagnosticsAwareInterface
{
    /**
     * @return array{
     *     attemptedSources: array<int, array<string, mixed>>,
     *     sourceErrors: array<int, array<string, mixed>>
     * }
     */
    public function diagnostics(): array;
}
