<?php

namespace App\Modules\SiteIntel\Application\Services\DomainLite;

final class DomainLiteDnsResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(string $domain): array
    {
        $aRecords = $this->queryDns($domain, defined('DNS_A') ? DNS_A : null);
        $aaaaRecords = $this->queryDns($domain, defined('DNS_AAAA') ? DNS_AAAA : null);
        $nsRecords = $this->queryDns($domain, defined('DNS_NS') ? DNS_NS : null);
        $mxRecords = $this->queryDns($domain, defined('DNS_MX') ? DNS_MX : null);
        $txtRecords = $this->queryDns($domain, defined('DNS_TXT') ? DNS_TXT : null);
        $caaRecords = $this->queryDns($domain, defined('DNS_CAA') ? DNS_CAA : null);
        $dmarcRecords = $this->queryDns('_dmarc.' . $domain, defined('DNS_TXT') ? DNS_TXT : null);

        $a = [];
        foreach ($aRecords as $record) {
            if (isset($record['ip'])) {
                $a[] = (string) $record['ip'];
            }
        }

        $aaaa = [];
        foreach ($aaaaRecords as $record) {
            if (isset($record['ipv6'])) {
                $aaaa[] = (string) $record['ipv6'];
            }
        }

        $ns = [];
        foreach ($nsRecords as $record) {
            if (isset($record['target'])) {
                $ns[] = rtrim((string) $record['target'], '.');
            }
        }

        $mx = [];
        foreach ($mxRecords as $record) {
            if (isset($record['target'])) {
                $mx[] = [
                    'host' => rtrim((string) $record['target'], '.'),
                    'priority' => (int) ($record['pri'] ?? 0),
                ];
            }
        }
        usort($mx, static fn (array $left, array $right): int => $left['priority'] <=> $right['priority']);

        $txt = [];
        foreach ($txtRecords as $record) {
            $text = trim((string) ($record['txt'] ?? ''));
            if ($text !== '') {
                $txt[] = $text;
            }
        }

        $caa = [];
        foreach ($caaRecords as $record) {
            $value = trim((string) ($record['value'] ?? ''));
            if ($value !== '') {
                $caa[] = $value;
            }
        }

        $dmarc = [];
        foreach ($dmarcRecords as $record) {
            $text = trim((string) ($record['txt'] ?? ''));
            if ($text !== '') {
                $dmarc[] = $text;
            }
        }

        $hasSpf = false;
        foreach ($txt as $entry) {
            if (str_starts_with(strtolower($entry), 'v=spf1')) {
                $hasSpf = true;
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
                'hasSpf' => $hasSpf,
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
}

