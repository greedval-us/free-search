<?php

namespace App\Modules\SiteIntel\Application\Services\SiteHealth;

use Carbon\Carbon;

final class SiteHealthSslInspector
{
    /**
     * @return array<string, mixed>
     */
    public function inspect(string $host, bool $isHttps): array
    {
        if ($host === '' || !$isHttps) {
            return $this->emptyPayload();
        }

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = @stream_socket_client(
            'ssl://' . $host . ':443',
            $errorNumber,
            $errorString,
            8,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($client === false) {
            return $this->emptyPayload();
        }

        $params = stream_context_get_params($client);
        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;

        if (!is_resource($certificate) && !is_object($certificate)) {
            return $this->emptyPayload();
        }

        $parsed = openssl_x509_parse($certificate);
        if (!is_array($parsed)) {
            return $this->emptyPayload();
        }

        $validFromTs = isset($parsed['validFrom_time_t']) ? (int) $parsed['validFrom_time_t'] : null;
        $validToTs = isset($parsed['validTo_time_t']) ? (int) $parsed['validTo_time_t'] : null;
        $daysRemaining = $validToTs !== null ? (int) floor(($validToTs - time()) / 86400) : null;

        return [
            'available' => true,
            'subject' => $parsed['subject']['CN'] ?? null,
            'issuer' => $parsed['issuer']['O'] ?? ($parsed['issuer']['CN'] ?? null),
            'validFrom' => $validFromTs ? Carbon::createFromTimestamp($validFromTs)->toIso8601String() : null,
            'validTo' => $validToTs ? Carbon::createFromTimestamp($validToTs)->toIso8601String() : null,
            'daysRemaining' => $daysRemaining,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'available' => false,
            'subject' => null,
            'issuer' => null,
            'validFrom' => null,
            'validTo' => null,
            'daysRemaining' => null,
        ];
    }
}

