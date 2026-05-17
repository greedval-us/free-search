<?php

namespace App\Modules\DomainInfraIntel\Application\Services\DomainInfraIntel;

use App\Modules\DomainInfraIntel\Application\Contracts\AsnLookupClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class AsnLookupClient implements AsnLookupClientInterface
{
    public function lookup(?string $ip): array
    {
        if ($ip === null || $ip === '') {
            return [];
        }

        try {
            $response = Http::timeout(10)->get('https://ipwho.is/' . rawurlencode($ip));
        } catch (ConnectionException) {
            return [];
        }

        if (!$response->ok() || !is_array($response->json())) {
            return [];
        }

        $data = $response->json();

        return [
            'ip' => $ip,
            'asn' => (string) ($data['connection']['asn'] ?? ''),
            'org' => (string) ($data['connection']['org'] ?? ''),
            'isp' => (string) ($data['connection']['isp'] ?? ''),
            'country' => (string) ($data['country'] ?? ''),
        ];
    }
}

