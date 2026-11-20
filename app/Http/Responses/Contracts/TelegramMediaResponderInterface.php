<?php

namespace App\Http\Responses\Contracts;

use Closure;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface TelegramMediaResponderInterface
{
    /**
     * @param  array{downloadToFile: Closure(string): string, download: array<string, mixed>}  $mediaPayload
     */
    public function respond(array $mediaPayload): BinaryFileResponse;
}
