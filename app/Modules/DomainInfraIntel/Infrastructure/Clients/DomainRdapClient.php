<?php

namespace App\Modules\DomainInfraIntel\Infrastructure\Clients;

use App\Modules\DomainInfraIntel\Application\Contracts\DomainRdapClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class DomainRdapClient implements DomainRdapClientInterface
{
    public function lookup(string $domain): array
    {
        $url = 'https://rdap.org/domain/' . rawurlencode($domain);

        try {
            $response = Http::timeout(15)->get($url);
        } catch (ConnectionException) {
            return [];
        }

        if (!$response->ok() || !is_array($response->json())) {
            return [];
        }

        $data = $response->json();

        return [
            'ldhName' => (string) ($data['ldhName'] ?? ''),
            'statuses' => is_array($data['status'] ?? null) ? array_values($data['status']) : [],
            'events' => is_array($data['events'] ?? null) ? array_values($data['events']) : [],
            'nameservers' => is_array($data['nameservers'] ?? null) ? array_values($data['nameservers']) : [],
        ];
    }
}

