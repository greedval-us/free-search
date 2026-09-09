<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Modules\SiteIntel\Application\Contracts\SiteHealthSslInspectorInterface;
use App\Modules\SiteIntel\Application\Support\SiteIntelConfig;
use App\Modules\SiteIntel\Support\SiteIntelTargetGuard;
use Carbon\Carbon;

final class SiteHealthSslInspector implements SiteHealthSslInspectorInterface
{
    public function __construct(
        private readonly SiteIntelTargetGuard $targetGuard,
        private readonly SiteIntelConfig $config,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function inspect(string $url): array
    {
        if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return $this->emptyPayload();
        }

        $target = $this->targetGuard->resolveSafeTarget($url);
        $context = stream_context_create([
            'ssl' => [
                'peer_name' => $target->host,
                'SNI_enabled' => true,
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = @stream_socket_client(
            $target->sslSocketAddress(),
            $errorNumber,
            $errorString,
            $this->config->httpTimeoutSeconds(),
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($client === false) {
            return $this->emptyPayload();
        }

        $params = stream_context_get_params($client);
        fclose($client);
        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;

        if (! is_resource($certificate) && ! is_object($certificate)) {
            return $this->emptyPayload();
        }

        $parsed = openssl_x509_parse($certificate);
        if (! is_array($parsed)) {
            return $this->emptyPayload();
        }

        $validFromTs = isset($parsed['validFrom_time_t']) ? (int) $parsed['validFrom_time_t'] : null;
        $validToTs = isset($parsed['validTo_time_t']) ? (int) $parsed['validTo_time_t'] : null;
        $daysRemaining = $validToTs !== null ? (int) floor(now()->diffInDays(Carbon::createFromTimestamp($validToTs), false)) : null;

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
