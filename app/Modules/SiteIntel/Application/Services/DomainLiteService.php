<?php

namespace App\Modules\SiteIntel\Application\Services;

use Carbon\Carbon;

final class DomainLiteService
{
    /**
     * @return array<string, mixed>
     */
    public function lookup(string $domain): array
    {
        $dns = $this->resolveDns($domain);
        $whois = $this->lookupWhois($domain);
        $risk = $this->buildRiskScore($dns, $whois);

        return [
            'domain' => $domain,
            'checkedAt' => Carbon::now()->toIso8601String(),
            'dns' => $dns,
            'whois' => $whois,
            'risk' => $risk,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveDns(string $domain): array
    {
        $aRecords = $this->queryDns($domain, defined('DNS_A') ? DNS_A : null);
        $aaaaRecords = $this->queryDns($domain, defined('DNS_AAAA') ? DNS_AAAA : null);
        $nsRecords = $this->queryDns($domain, defined('DNS_NS') ? DNS_NS : null);
        $mxRecords = $this->queryDns($domain, defined('DNS_MX') ? DNS_MX : null);
        $txtRecords = $this->queryDns($domain, defined('DNS_TXT') ? DNS_TXT : null);
        $caaRecords = $this->queryDns($domain, defined('DNS_CAA') ? DNS_CAA : null);
        $dmarcRecords = $this->queryDns('_dmarc.' . $domain, defined('DNS_TXT') ? DNS_TXT : null);

        $a = [];
        foreach ($aRecords ?: [] as $record) {
            if (isset($record['ip'])) {
                $a[] = (string) $record['ip'];
            }
        }

        $aaaa = [];
        foreach ($aaaaRecords ?: [] as $record) {
            if (isset($record['ipv6'])) {
                $aaaa[] = (string) $record['ipv6'];
            }
        }

        $ns = [];
        foreach ($nsRecords ?: [] as $record) {
            if (isset($record['target'])) {
                $ns[] = rtrim((string) $record['target'], '.');
            }
        }

        $mx = [];
        foreach ($mxRecords ?: [] as $record) {
            if (isset($record['target'])) {
                $mx[] = [
                    'host' => rtrim((string) $record['target'], '.'),
                    'priority' => (int) ($record['pri'] ?? 0),
                ];
            }
        }

        usort($mx, static fn (array $left, array $right): int => $left['priority'] <=> $right['priority']);

        $txt = [];
        foreach ($txtRecords ?: [] as $record) {
            $text = trim((string) ($record['txt'] ?? ''));
            if ($text !== '') {
                $txt[] = $text;
            }
        }

        $caa = [];
        foreach ($caaRecords ?: [] as $record) {
            $value = trim((string) ($record['value'] ?? ''));
            if ($value !== '') {
                $caa[] = $value;
            }
        }

        $dmarc = [];
        foreach ($dmarcRecords ?: [] as $record) {
            $text = trim((string) ($record['txt'] ?? ''));
            if ($text !== '') {
                $dmarc[] = $text;
            }
        }

        $spf = false;
        foreach ($txt as $entry) {
            if (str_starts_with(strtolower($entry), 'v=spf1')) {
                $spf = true;
                break;
            }
        }

        return [
            'a' => array_values(array_unique($a)),
            'aaaa' => array_values(array_unique($aaaa)),
            'ns' => array_values(array_unique($ns)),
            'mx' => $mx,
            'txt' => $txt,
            'caa' => array_values(array_unique($caa)),
            'emailSecurity' => [
                'hasSpf' => $spf,
                'hasDmarc' => count($dmarc) > 0,
                'dmarc' => $dmarc,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function queryDns(string $host, ?int $type): array
    {
        if ($type === null) {
            return [];
        }

        $result = @dns_get_record($host, $type);

        return is_array($result) ? $result : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function lookupWhois(string $domain): array
    {
        $whoisServer = $this->resolveWhoisServer($domain);
        if ($whoisServer === null) {
            return [
                'server' => null,
                'available' => false,
                'createdAt' => null,
                'updatedAt' => null,
                'expiresAt' => null,
                'registrar' => null,
                'country' => null,
                'sample' => null,
            ];
        }

        $response = $this->queryWhois($whoisServer, $domain);
        if ($response === null || trim($response) === '') {
            return [
                'server' => $whoisServer,
                'available' => false,
                'createdAt' => null,
                'updatedAt' => null,
                'expiresAt' => null,
                'registrar' => null,
                'country' => null,
                'sample' => null,
            ];
        }

        $createdAt = $this->extractDate($response, [
            'Creation Date',
            'Created On',
            'Created',
            'Registered on',
            'Domain Registration Date',
        ]);
        $updatedAt = $this->extractDate($response, [
            'Updated Date',
            'Last Updated',
            'Modified',
        ]);
        $expiresAt = $this->extractDate($response, [
            'Registry Expiry Date',
            'Registrar Registration Expiration Date',
            'Expiry Date',
            'Expiration Date',
            'paid-till',
        ]);

        $registrar = $this->extractValue($response, [
            'Registrar',
            'Sponsoring Registrar',
        ]);
        $country = $this->extractValue($response, [
            'Registrant Country',
            'Country',
        ]);

        $sampleLines = preg_split('/\r\n|\r|\n/', $response) ?: [];
        $sampleLines = array_slice(array_values(array_filter($sampleLines, static fn (string $line): bool => trim($line) !== '')), 0, 30);

        return [
            'server' => $whoisServer,
            'available' => true,
            'createdAt' => $createdAt?->toIso8601String(),
            'updatedAt' => $updatedAt?->toIso8601String(),
            'expiresAt' => $expiresAt?->toIso8601String(),
            'registrar' => $registrar,
            'country' => $country,
            'sample' => implode("\n", $sampleLines),
        ];
    }

    private function resolveWhoisServer(string $domain): ?string
    {
        $ianaResponse = $this->queryWhois('whois.iana.org', $domain);
        if ($ianaResponse === null) {
            return null;
        }

        if (preg_match('/^refer:\s*(.+)$/im', $ianaResponse, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    private function queryWhois(string $server, string $domain): ?string
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

    /**
     * @param string[] $labels
     */
    private function extractDate(string $text, array $labels): ?Carbon
    {
        foreach ($labels as $label) {
            $value = $this->extractByLabel($text, $label);
            if ($value === null) {
                continue;
            }

            $timestamp = strtotime($value);
            if ($timestamp === false) {
                continue;
            }

            return Carbon::createFromTimestamp($timestamp);
        }

        return null;
    }

    /**
     * @param string[] $labels
     */
    private function extractValue(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $value = $this->extractByLabel($text, $label);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractByLabel(string $text, string $label): ?string
    {
        $pattern = '/^' . preg_quote($label, '/') . '\s*:\s*(.+)$/im';
        if (preg_match($pattern, $text, $matches) !== 1) {
            return null;
        }

        return trim((string) $matches[1]);
    }

    /**
     * @param array<string, mixed> $dns
     * @param array<string, mixed> $whois
     * @return array<string, mixed>
     */
    private function buildRiskScore(array $dns, array $whois): array
    {
        $score = 0;
        $signals = [];

        if (empty($dns['a']) && empty($dns['aaaa'])) {
            $score += 25;
            $signals[] = 'no_a_or_aaaa_records';
        }

        if (empty($dns['ns'])) {
            $score += 20;
            $signals[] = 'no_ns_records';
        }

        if (empty($dns['mx'])) {
            $score += 10;
            $signals[] = 'no_mx_records';
        }

        if (($dns['emailSecurity']['hasSpf'] ?? false) !== true) {
            $score += 8;
            $signals[] = 'missing_spf';
        }

        if (($dns['emailSecurity']['hasDmarc'] ?? false) !== true) {
            $score += 8;
            $signals[] = 'missing_dmarc';
        }

        if (($whois['available'] ?? false) !== true) {
            $score += 8;
            $signals[] = 'whois_unavailable';
        }

        if (is_string($whois['expiresAt'] ?? null)) {
            $expiresAt = Carbon::parse($whois['expiresAt']);
            $daysToExpiry = Carbon::now()->diffInDays($expiresAt, false);

            if ($daysToExpiry < 0) {
                $score += 40;
                $signals[] = 'domain_expired';
            } elseif ($daysToExpiry <= 30) {
                $score += 20;
                $signals[] = 'domain_expires_soon';
            } elseif ($daysToExpiry <= 90) {
                $score += 10;
                $signals[] = 'domain_expires_in_90_days';
            }
        }

        $score = max(0, min(100, $score));
        $level = match (true) {
            $score >= 60 => 'high',
            $score >= 30 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
            'signals' => array_values(array_unique($signals)),
        ];
    }
}
