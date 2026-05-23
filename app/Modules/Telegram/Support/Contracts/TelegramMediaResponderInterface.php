<?php

namespace App\Modules\Telegram\Support\Contracts;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface TelegramMediaResponderInterface
{
    /**
     * @param array<string, mixed> $mediaPayload
     */
    public function respond(array $mediaPayload): BinaryFileResponse;
}

