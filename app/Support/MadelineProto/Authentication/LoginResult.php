<?php

declare(strict_types=1);

namespace App\Support\MadelineProto\Authentication;

final readonly class LoginResult
{
    public function __construct(
        public ?LoginStage $stage,
        public ?string $error = null,
        public int $retryAfter = 0,
    ) {}
}
