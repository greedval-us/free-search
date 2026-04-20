<?php

namespace App\Modules\SiteIntel\Application\Services\DomainLite;

final class DomainLiteWhoisClient
{
    public function query(string $server, string $domain): ?string
    {
        $socket = @fsockopen($server, 43, $errorNumber, $errorString, 8);
        if ($socket === false) {
            return null;
        }

        stream_set_timeout($socket, 8);
        fwrite($socket, $domain . "\r\n");

        $response = '';
        while (!feof($socket)) {
            $chunk = fgets($socket, 2048);
            if ($chunk === false) {
                break;
            }

            $response .= $chunk;
            if (strlen($response) > 120000) {
                break;
            }
        }

        fclose($socket);

        return $response;
    }
}

