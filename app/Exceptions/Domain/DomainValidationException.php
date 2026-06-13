<?php

namespace App\Exceptions\Domain;

use InvalidArgumentException;

class DomainValidationException extends InvalidArgumentException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
