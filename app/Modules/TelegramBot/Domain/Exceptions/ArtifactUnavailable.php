<?php

namespace App\Modules\TelegramBot\Domain\Exceptions;

use RuntimeException;

final class ArtifactUnavailable extends RuntimeException
{
    public function __construct(public readonly string $reason = 'file_unavailable')
    {
        parent::__construct($reason);
    }
}
