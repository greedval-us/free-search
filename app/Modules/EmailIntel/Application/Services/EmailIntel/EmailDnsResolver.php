<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailDnsResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(string $domain): array
    {
        return [
            'mx' => $this->mxRecords($domain),
            'a' => $this->recordValues($this->queryDns($domain, defined('DNS_A') ? DNS_A : null), 'ip'),
            'aaaa' => $this->recordValues($this->queryDns($domain, defined('DNS_AAAA') ? DNS_AAAA : null), 'ipv6'),
            'txt' => $this->txtRecords($domain),
            'dmarc' => $this->txtRecords('_dmarc.' . $domain),
        ];
    }

    /**
     * @return array<int, array{host: string, priority: int}>
     */
    private function mxRecords(string $domain): array
    {
        $records = $this->queryDns($domain, defined('DNS_MX') ? DNS_MX : null);
        $mx = [];

        foreach ($records as $record) {
            if (isset($record['target'])) {
                $mx[] = [
                    'host' => rtrim((string) $record['target'], '.'),
                    'priority' => (int) ($record['pri'] ?? 0),
                ];
            }
        }

        usort($mx, static fn (array $left, array $right): int => $left['priority'] <=> $right['priority']);

        return $mx;
    }

    /**
     * @return array<int, string>
     */
    private function txtRecords(string $domain): array
    {
        $records = $this->queryDns($domain, defined('DNS_TXT') ? DNS_TXT : null);

        return $this->recordValues($records, 'txt');
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array<int, string>
     */
    private function recordValues(array $records, string $key): array
    {
        $values = [];

        foreach ($records as $record) {
            $value = trim((string) ($record[$key] ?? ''));
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function queryDns(string $host, ?int $type): array
    {
        if ($type === null) {
            return [];
        }

        $records = @dns_get_record($host, $type);

        return is_array($records) ? $records : [];
    }
}
