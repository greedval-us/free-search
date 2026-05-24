<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Modules\SiteIntel\Application\Contracts\DomainLiteWhoisClientInterface;
use App\Modules\SiteIntel\Application\Support\SiteIntelConfig;

final class DomainLiteWhoisClient implements DomainLiteWhoisClientInterface
{
    public function __construct(private readonly SiteIntelConfig $config)
    {
    }

    public function query(string $server, string $domain): ?string
    {
        $socket = @fsockopen($server, 43, $errorNumber, $errorString, $this->connectTimeoutSeconds());
        if ($socket === false) {
            return null;
        }

        stream_set_timeout($socket, $this->readTimeoutSeconds());
        fwrite($socket, $domain . "\r\n");

        $response = '';
        while (!feof($socket)) {
            $chunk = fgets($socket, $this->readChunkSize());
            if ($chunk === false) {
                break;
            }

            $response .= $chunk;
            if (strlen($response) > $this->maxResponseBytes()) {
                break;
            }
        }

        fclose($socket);

        return $response;
    }

    private function connectTimeoutSeconds(): int
    {
        return $this->config->whoisConnectTimeoutSeconds();
    }

    private function readTimeoutSeconds(): int
    {
        return $this->config->whoisReadTimeoutSeconds();
    }

    private function readChunkSize(): int
    {
        return $this->config->whoisReadChunkSize();
    }

    private function maxResponseBytes(): int
    {
        return $this->config->whoisMaxResponseBytes();
    }
}
