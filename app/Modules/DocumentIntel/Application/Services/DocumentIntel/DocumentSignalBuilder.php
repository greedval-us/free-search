<?php

namespace App\Modules\DocumentIntel\Application\Services\DocumentIntel;

final class DocumentSignalBuilder
{
    /**
     * @param array<string, mixed>|null $dns
     * @param array<string, mixed>|null $whois
     * @return array<int, string>
     */
    public function build(?array $dns, ?array $whois, ?string $domain): array
    {
        $signals = [];

        if ($domain === null) {
            return ['domain_not_detected'];
        }

        if ($dns !== null && empty($dns['a']) && empty($dns['aaaa'])) {
            $signals[] = 'no_dns_resolution';
        }

        if ($dns !== null && ($dns['emailSecurity']['hasSpf'] ?? false) !== true) {
            $signals[] = 'missing_spf';
        }

        if ($dns !== null && ($dns['emailSecurity']['hasDmarc'] ?? false) !== true) {
            $signals[] = 'missing_dmarc';
        }

        if ($whois !== null && ($whois['available'] ?? false) !== true) {
            $signals[] = 'whois_unavailable';
        }

        if ($signals === []) {
            $signals[] = 'baseline_ok';
        }

        return $signals;
    }
}
