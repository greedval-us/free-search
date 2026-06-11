<?php

namespace App\Exceptions;

use RuntimeException;

class SubscriptionActivationException extends RuntimeException
{
    public const INVALID = 'invalid';

    public const USED = 'used';

    public const EXPIRED = 'expired';

    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
