<?php

declare(strict_types=1);

namespace App\Support\MadelineProto\Authentication;

use SensitiveParameter;

interface SessionAuthenticator
{
    public function submit(string $name, LoginStage $stage, #[SensitiveParameter] string $value): LoginResult;

    public function inspect(string $name): LoginResult;
}
