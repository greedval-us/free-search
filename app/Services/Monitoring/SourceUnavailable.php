<?php

namespace App\Services\Monitoring;

use RuntimeException;

final class SourceUnavailable extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly ?int $retryAfter = null)
    {
        parent::__construct($reason);
    }
}
