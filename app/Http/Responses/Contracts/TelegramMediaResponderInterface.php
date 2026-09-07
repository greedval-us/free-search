<?php

namespace App\Http\Responses\Contracts;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface TelegramMediaResponderInterface
{
    /**
     * @param array<string, mixed> $mediaPayload
     */
    public function respond(array $mediaPayload): BinaryFileResponse;
}
