<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use OverflowException;

final class SiteIntelBoundedResponseStream extends Stream
{
    private int $bytesWritten = 0;

    private bool $exceeded = false;

    public function __construct(private readonly int $maxBytes)
    {
        parent::__construct(Utils::tryFopen('php://memory', 'w+'));
    }

    public function write(string $string): int
    {
        if (strlen($string) > $this->maxBytes - $this->bytesWritten) {
            $this->exceeded = true;

            throw new OverflowException('Site Intel response body limit exceeded.');
        }

        $written = parent::write($string);
        $this->bytesWritten += $written;

        return $written;
    }

    public function limitExceeded(): bool
    {
        return $this->exceeded;
    }
}
