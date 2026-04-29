<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailDmarcReportAnalyzer
{
    /**
     * @param array<string, mixed> $dmarc
     * @return array{destinations: array<int, array{kind: string, mailbox: string, domain: string, external: bool}>, externalDestinations: array<int, array{kind: string, mailbox: string, domain: string, external: bool}>, hasExternalReporting: bool}
     */
    public function analyze(string $domain, array $dmarc): array
    {
        $destinations = [
            ...$this->destinations($domain, 'rua', $dmarc['rua'] ?? []),
            ...$this->destinations($domain, 'ruf', $dmarc['ruf'] ?? []),
        ];
        $externalDestinations = array_values(array_filter(
            $destinations,
            static fn (array $destination): bool => $destination['external'],
        ));

        return [
            'destinations' => $destinations,
            'externalDestinations' => $externalDestinations,
            'hasExternalReporting' => $externalDestinations !== [],
        ];
    }

    /**
     * @param array<int, string> $mailboxes
     * @return array<int, array{kind: string, mailbox: string, domain: string, external: bool}>
     */
    private function destinations(string $domain, string $kind, array $mailboxes): array
    {
        $destinations = [];

        foreach ($mailboxes as $mailbox) {
            $normalized = $this->normalizeMailbox($mailbox);
            if ($normalized === null) {
                continue;
            }

            $reportDomain = substr(strrchr($normalized, '@') ?: '', 1);
            if ($reportDomain === '') {
                continue;
            }

            $destinations[] = [
                'kind' => $kind,
                'mailbox' => $normalized,
                'domain' => $reportDomain,
                'external' => ! $this->isSameDomainScope($domain, $reportDomain),
            ];
        }

        return $destinations;
    }

    private function normalizeMailbox(string $mailbox): ?string
    {
        $value = preg_replace('/^mailto:/i', '', trim($mailbox)) ?? '';
        $value = preg_replace('/!.+$/', '', $value) ?? $value;

        return filter_var($value, FILTER_VALIDATE_EMAIL) === false ? null : mb_strtolower($value);
    }

    private function isSameDomainScope(string $domain, string $reportDomain): bool
    {
        $domain = mb_strtolower($domain);
        $reportDomain = mb_strtolower($reportDomain);

        return $reportDomain === $domain || str_ends_with($reportDomain, '.' . $domain);
    }
}
