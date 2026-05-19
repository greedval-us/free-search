<?php

namespace App\Modules\DomainInfraIntel\Infrastructure\Clients;

use App\Modules\DomainInfraIntel\Application\Contracts\CertificateTransparencyClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class CertificateTransparencyClient implements CertificateTransparencyClientInterface
{
    public function lookup(string $domain): array
    {
        $url = 'https://crt.sh/?q=' . rawurlencode($domain) . '&output=json';

        try {
            $response = Http::timeout(20)->get($url);
        } catch (ConnectionException) {
            return [];
        }

        if (!$response->ok()) {
            return [];
        }

        $rows = $response->json();
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach (array_slice($rows, 0, 80) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $out[] = [
                'issuer' => (string) ($row['issuer_name'] ?? ''),
                'nameValue' => (string) ($row['name_value'] ?? ''),
                'notBefore' => (string) ($row['not_before'] ?? ''),
                'notAfter' => (string) ($row['not_after'] ?? ''),
            ];
        }

        return $out;
    }
}

