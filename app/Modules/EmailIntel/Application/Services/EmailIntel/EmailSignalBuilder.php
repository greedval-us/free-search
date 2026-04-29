<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailSignalBuilder
{
    /**
     * @param array<string, mixed> $domainIntel
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $localPartAnalysis
     * @return array<int, array{type: string, level: string, message: string}>
     */
    public function build(array $domainIntel, array $profile, array $localPartAnalysis): array
    {
        $dns = $domainIntel['dns'];
        $spf = $domainIntel['spf'];
        $dmarc = $domainIntel['dmarc'];
        $dmarcReports = $domainIntel['dmarcReports'];
        $signals = [];

        if (count($dns['mx']) === 0) {
            $signals[] = ['type' => 'missing_mx', 'level' => 'high', 'message' => 'Domain has no MX records.'];
        }

        if (count($dns['a']) + count($dns['aaaa']) === 0) {
            $signals[] = ['type' => 'domain_not_resolving', 'level' => 'medium', 'message' => 'Domain has no A/AAAA records.'];
        }

        $signals = [
            ...$signals,
            ...$this->spfSignals((string) $spf['strictness']),
            ...$this->dmarcSignals((string) $dmarc['strength']),
        ];

        if ((bool) $profile['isDisposable']) {
            $signals[] = ['type' => 'disposable_provider', 'level' => 'high', 'message' => 'Domain matches a local disposable-mail list.'];
        }

        if ((bool) $profile['isRoleAccount']) {
            $signals[] = ['type' => 'role_account', 'level' => 'low', 'message' => 'Local part looks like a role/shared inbox.'];
        }

        if ((bool) $localPartAnalysis['looksRandom']) {
            $signals[] = ['type' => 'random_local_part', 'level' => 'low', 'message' => 'Local part looks random or machine-generated.'];
        }

        if ((bool) $profile['isFreeProvider']) {
            $signals[] = ['type' => 'free_provider', 'level' => 'info', 'message' => 'Domain is a common free mailbox provider.'];
        }

        if ((bool) $dmarcReports['hasExternalReporting']) {
            $signals[] = ['type' => 'external_dmarc_reporting', 'level' => 'low', 'message' => 'DMARC reports are sent to an external domain.'];
        }

        if ($signals === []) {
            $signals[] = ['type' => 'baseline_ok', 'level' => 'positive', 'message' => 'No strong technical risk signals detected.'];
        }

        return $signals;
    }

    /**
     * @return array<int, array{type: string, level: string, message: string}>
     */
    private function spfSignals(string $strictness): array
    {
        return match (true) {
            $strictness === 'missing' => [['type' => 'missing_spf', 'level' => 'medium', 'message' => 'SPF record is missing.']],
            $strictness === 'open' => [['type' => 'open_spf', 'level' => 'high', 'message' => 'SPF policy allows all senders.']],
            in_array($strictness, ['soft', 'neutral', 'unknown'], true) => [['type' => 'weak_spf', 'level' => 'low', 'message' => 'SPF policy is present but not strict.']],
            default => [],
        };
    }

    /**
     * @return array<int, array{type: string, level: string, message: string}>
     */
    private function dmarcSignals(string $strength): array
    {
        return match ($strength) {
            'missing' => [['type' => 'missing_dmarc', 'level' => 'medium', 'message' => 'DMARC record is missing.']],
            'monitoring' => [['type' => 'monitoring_dmarc', 'level' => 'low', 'message' => 'DMARC is in monitoring mode only.']],
            default => [],
        };
    }
}
