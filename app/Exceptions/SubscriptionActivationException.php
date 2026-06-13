<?php

namespace App\Exceptions;

use RuntimeException;

final class SubscriptionActivationException extends RuntimeException
{
    private function __construct(
        public readonly string $reason,
        private readonly string $messageKey,
    ) {
        parent::__construct($reason);
    }

    public static function invalid(): self
    {
        return new self('invalid', 'validation.custom.activation_token.invalid');
    }

    public static function used(): self
    {
        return new self('used', 'validation.custom.activation_token.used');
    }

    public static function expired(): self
    {
        return new self('expired', 'validation.custom.activation_token.expired');
    }

    public function messageKey(): string
    {
        return $this->messageKey;
    }
}
