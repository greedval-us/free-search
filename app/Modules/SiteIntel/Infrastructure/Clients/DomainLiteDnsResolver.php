<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Modules\SiteIntel\Application\Contracts\DomainLiteDnsResolverInterface;

final class DomainLiteDnsResolver implements DomainLiteDnsResolverInterface
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
        $dnskeyRecords = $this->queryDns($domain, defined('DNS_DNSKEY') ? DNS_DNSKEY : null);
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

        $spfRecord = null;
        $hasSpf = false;
        foreach ($txt as $entry) {
            if (str_starts_with(strtolower($entry), 'v=spf1')) {
                $hasSpf = true;
                $spfRecord = $entry;
                break;
            }
        }

        $spfPolicy = $this->parseSpfPolicy($spfRecord);
        $dmarcPolicy = $this->parseDmarcPolicy($dmarc[0] ?? null);

        return [
            'a' => array_values(array_unique($a)),
            'aaaa' => array_values(array_unique($aaaa)),
            'ns' => array_values(array_unique($ns)),
            'mx' => $mx,
            'txt' => $txt,
            'caa' => array_values(array_unique($caa)),
            'dnssec' => [
                'enabled' => count($dnskeyRecords) > 0,
                'dnskeyCount' => count($dnskeyRecords),
            ],
            'emailSecurity' => [
                'hasSpf' => $hasSpf,
                'spfRecord' => $spfRecord,
                'spfPolicy' => $spfPolicy,
                'hasDmarc' => count($dmarc) > 0,
                'dmarc' => $dmarc,
                'dmarcPolicy' => $dmarcPolicy,
            ],
        ];
    }

    /**
     * @return array{allQualifier: string|null, includeCount: int, isStrict: bool}
     */
    private function parseSpfPolicy(?string $spfRecord): array
    {
        if ($spfRecord === null || trim($spfRecord) === '') {
            return [
                'allQualifier' => null,
                'includeCount' => 0,
                'isStrict' => false,
            ];
        }

        $lower = strtolower($spfRecord);
        $includeCount = substr_count($lower, 'include:');
        $allQualifier = null;

        if (preg_match('/([~?+-])all\b/i', $spfRecord, $matches) === 1) {
            $allQualifier = $matches[1];
        } elseif (preg_match('/\ball\b/i', $spfRecord) === 1) {
            $allQualifier = '+';
        }

        return [
            'allQualifier' => $allQualifier,
            'includeCount' => $includeCount,
            'isStrict' => $allQualifier === '-',
        ];
    }

    /**
     * @return array{policy: string|null, percentage: int|null}
     */
    private function parseDmarcPolicy(?string $dmarcRecord): array
    {
        if ($dmarcRecord === null || trim($dmarcRecord) === '') {
            return [
                'policy' => null,
                'percentage' => null,
            ];
        }

        $policy = null;
        $percentage = null;

        if (preg_match('/\bp=([a-z]+)/i', $dmarcRecord, $policyMatches) === 1) {
            $policy = strtolower($policyMatches[1]);
        }

        if (preg_match('/\bpct=(\d{1,3})/i', $dmarcRecord, $pctMatches) === 1) {
            $percentage = max(0, min(100, (int) $pctMatches[1]));
        }

        return [
            'policy' => $policy,
            'percentage' => $percentage,
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

