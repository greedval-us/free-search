<?php

namespace App\Modules\Telegram\Tracking;

use App\Exceptions\PublicException;

final class TrackingException extends PublicException
{
    public function __construct(public readonly string $reason, public readonly int $retryAfter = 0)
    {
        parent::__construct('telegram_tracking.errors.'.$reason, 422, $reason);
    }
}
