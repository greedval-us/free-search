<?php

namespace App\Modules\SiteIntel\Application\Services\DomainLite;

final class DomainLiteWhoisClient
{
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
        return max(1, (int) config('osint.site_intel.whois.connect_timeout_seconds', 8));
    }

    private function readTimeoutSeconds(): int
    {
        return max(1, (int) config('osint.site_intel.whois.read_timeout_seconds', 8));
    }

    private function readChunkSize(): int
    {
        return max(128, (int) config('osint.site_intel.whois.read_chunk_size', 2048));
    }

    private function maxResponseBytes(): int
    {
        return max(1024, (int) config('osint.site_intel.whois.max_response_bytes', 120000));
    }
}
