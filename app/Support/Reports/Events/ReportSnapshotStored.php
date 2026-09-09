<?php

namespace App\Support\Reports\Events;

use Carbon\CarbonInterface;

final readonly class ReportSnapshotStored
{
    /** @param array<string, mixed> $parameters */
    public function __construct(public int $userId, public string $feature, public array $parameters, public CarbonInterface $expiresAt) {}
}
