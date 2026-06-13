<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class PublicException extends RuntimeException
{
    /**
     * @param array<string, string|int|float> $replace
     */
    public function __construct(
        private readonly string $translationKey,
        private readonly int $status = 422,
        private readonly string $errorCode = 'public_error',
        private readonly array $replace = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($translationKey, $status, $previous);
    }

    public function translationKey(): string
    {
        return $this->translationKey;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, string|int|float>
     */
    public function replace(): array
    {
        return $this->replace;
    }
}
