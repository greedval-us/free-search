<?php

namespace App\Modules\DomainInfraIntel\Application\Services;

use App\Modules\SiteIntel\Support\DomainNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class DomainInfraIntelService
{
    public function inspect(string $domain): array
    {
        $normalized = DomainNormalizer::normalizeDomain($domain);
        if ($normalized === null) {
            return ['domain' => $domain, 'error' => 'Invalid domain'];
        }

        $ips = $this->resolveIps($normalized);
        $asn = $this->resolveAsn($ips[0] ?? null);

        return [
            'domain' => $normalized,
            'ips' => $ips,
            'rdap' => $this->fetchRdap($normalized),
            'crtsh' => $this->fetchCrtSh($normalized),
            'asn' => $asn,
            'neighbors' => $this->neighborDomains($ips),
        ];
    }

    private function resolveIps(string $domain): array
    {
        $records = dns_get_record($domain, DNS_A) ?: [];
        $ips = [];
        foreach ($records as $record) {
            $ip = (string) ($record['ip'] ?? '');
            if ($ip !== '') {
                $ips[] = $ip;
            }
        }

        return array_values(array_unique($ips));
    }

    private function fetchRdap(string $domain): array
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

    private function fetchCrtSh(string $domain): array
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

    private function resolveAsn(?string $ip): array
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

    private function neighborDomains(array $ips): array
    {
        $neighbors = [];
        foreach ($ips as $ip) {
            $host = @gethostbyaddr((string) $ip);
            if (is_string($host) && $host !== '' && $host !== $ip) {
                $neighbors[] = $host;
            }
        }

        return array_values(array_unique(array_filter($neighbors)));
    }
}

