<?php

namespace App\Support\MadelineProto;

use RuntimeException;

final class SessionOperationException extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly int $retryAfter)
    {
        parent::__construct($reason);
    }
}
