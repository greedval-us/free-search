<?php

declare(strict_types=1);

namespace App\Support\MadelineProto\Authentication;

use RuntimeException;

final class SessionConnectionException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
