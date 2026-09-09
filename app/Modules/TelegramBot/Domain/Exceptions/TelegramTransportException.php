<?php

namespace App\Modules\TelegramBot\Domain\Exceptions;

use RuntimeException;

final class TelegramTransportException extends RuntimeException
{
    public function __construct(public readonly int $apiCode = 0, public readonly ?int $retryAfter = null)
    {
        // Never include the Bot API request URL, token, or response body in queue failures.
        parent::__construct('Telegram delivery failed (API code '.$apiCode.').');
    }
}
